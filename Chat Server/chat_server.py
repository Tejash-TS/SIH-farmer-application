"""
FastAPI WebSocket Server for Real-time Chat and Announcements
Requirements: fastapi, uvicorn, python-socketio, aiofiles, mysql-connector-python
"""

from fastapi import FastAPI, WebSocket, WebSocketDisconnect, HTTPException, Depends, Query
from fastapi.middleware.cors import CORSMiddleware
from contextlib import asynccontextmanager
import mysql.connector
from mysql.connector import Error
from datetime import datetime
from typing import List, Dict, Set
import json
import logging
import asyncio
from pydantic import BaseModel

# Logging setup
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Database Configura
DB_CONFIG = {
    'host': 'mysql',
    'user': 'root',
    'password': 'root',
    'database': 'sih',
    'port': 3306
}

# Models
class ChatMessage(BaseModel):
    sender_id: int
    receiver_id: int
    message_text: str

class Announcement(BaseModel):
    title: str
    description: str
    target_role: str = 'all'  # 'all', 'farmer', 'buyer', 'consultant', or comma-separated
    sender_id: int

class MessageData(BaseModel):
    type: str  # 'chat', 'announcement', 'typing', 'online_status'
    sender_id: int
    receiver_id: int = None
    message_text: str = None
    target_role: str = None

# Connection Manager
class ConnectionManager:
    def __init__(self):
        self.active_connections: Dict[int, WebSocket] = {}
        self.user_typing: Set[tuple] = set()

    async def connect(self, user_id: int, websocket: WebSocket):
        await websocket.accept()
        self.active_connections[user_id] = websocket
        logger.info(f"User {user_id} connected. Active: {len(self.active_connections)}")

    def disconnect(self, user_id: int):
        if user_id in self.active_connections:
            del self.active_connections[user_id]
            logger.info(f"User {user_id} disconnected. Active: {len(self.active_connections)}")

    async def broadcast_to_user(self, user_id: int, message: dict):
        if user_id in self.active_connections:
            try:
                await self.active_connections[user_id].send_json(message)
            except Exception as e:
                logger.error(f"Error sending message to user {user_id}: {e}")
                self.disconnect(user_id)

    async def broadcast_announcement(self, message: dict, target_role: str = 'all'):
        """Broadcast announcement to users with specific role or all users"""
        disconnected_users = []
        for user_id, connection in self.active_connections.items():
            try:
                # In production, check user role from DB before sending
                await connection.send_json(message)
            except Exception as e:
                logger.error(f"Error broadcasting to user {user_id}: {e}")
                disconnected_users.append(user_id)
        
        for user_id in disconnected_users:
            self.disconnect(user_id)

    def get_online_users(self) -> List[int]:
        return list(self.active_connections.keys())

manager = ConnectionManager()

# Database Helper Functions
def get_db_connection():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except Error as e:
        logger.error(f"Database connection error: {e}")
        raise

async def save_chat_message(sender_id: int, receiver_id: int, message_text: str):
    """Save chat message to database"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        query = """
            INSERT INTO chat_messages (sender_id, receiver_id, message_text, is_read, created_by, created_on)
            VALUES (%s, %s, %s, 0, %s, NOW())
        """
        
        cursor.execute(query, (sender_id, receiver_id, message_text, sender_id))
        conn.commit()
        message_id = cursor.lastrowid
        
        cursor.close()
        conn.close()
        
        return message_id
    except Error as e:
        logger.error(f"Error saving chat message: {e}")
        return None

async def get_chat_history(user_id1: int, user_id2: int, limit: int = 50):
    """Retrieve chat history between two users"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        query = """
            SELECT m.message_id, m.sender_id, m.receiver_id, m.message_text, m.is_read, m.created_on
            FROM chat_messages m
            WHERE (m.sender_id = %s AND m.receiver_id = %s) 
               OR (m.sender_id = %s AND m.receiver_id = %s)
            ORDER BY m.created_on DESC
            LIMIT %s
        """
        
        cursor.execute(query, (user_id1, user_id2, user_id2, user_id1, limit))
        messages = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        return list(reversed(messages))  # Return oldest first
    except Error as e:
        logger.error(f"Error fetching chat history: {e}")
        return []

async def get_announcements(user_id: int = None, role: str = None):
    """Retrieve announcements for user"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        query = """
            SELECT a.announcement_id, a.title, a.description, a.target_role, a.sender_id, a.created_on,
                   COALESCE(ar.read_on, NULL) as read_on
            FROM announcements a
            LEFT JOIN announcement_reads ar ON a.announcement_id = ar.announcement_id AND ar.user_id = %s
            WHERE a.is_active = 1 AND (a.target_role = 'all' OR a.target_role LIKE %s OR a.target_role LIKE CONCAT('%%,', %s, '%%') OR a.target_role LIKE CONCAT(%s, ',%%'))
            ORDER BY a.created_on DESC
            LIMIT 50
        """
        
        cursor.execute(query, (user_id, f'%{role}%', role, role))
        announcements = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        return announcements
    except Error as e:
        logger.error(f"Error fetching announcements: {e}")
        return []

async def save_announcement(title: str, description: str, target_role: str, sender_id: int):
    """Save announcement to database"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        query = """
            INSERT INTO announcements (title, description, target_role, sender_id, created_by, created_on)
            VALUES (%s, %s, %s, %s, %s, NOW())
        """
        
        cursor.execute(query, (title, description, target_role, sender_id, sender_id))
        conn.commit()
        announcement_id = cursor.lastrowid
        
        cursor.close()
        conn.close()
        
        return announcement_id
    except Error as e:
        logger.error(f"Error saving announcement: {e}")
        return None

async def mark_announcement_read(announcement_id: int, user_id: int):
    """Mark announcement as read for user"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        
        query = """
            INSERT IGNORE INTO announcement_reads (announcement_id, user_id, read_on)
            VALUES (%s, %s, NOW())
        """
        
        cursor.execute(query, (announcement_id, user_id))
        conn.commit()
        
        cursor.close()
        conn.close()
        
        return True
    except Error as e:
        logger.error(f"Error marking announcement read: {e}")
        return False

async def get_user_role(user_id: int):
    """Get user role from database"""
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        query = "SELECT role FROM users WHERE user_id = %s"
        cursor.execute(query, (user_id,))
        user = cursor.fetchone()
        
        cursor.close()
        conn.close()
        
        return user['role'] if user else None
    except Error as e:
        logger.error(f"Error fetching user role: {e}")
        return None

# FastAPI App Setup
@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup
    logger.info("WebSocket server started")
    yield
    # Shutdown
    logger.info("WebSocket server shutting down")

app = FastAPI(title="SIH Chat & Announcement Server", lifespan=lifespan)

# CORS Configuration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Routes

@app.get("/api/health")
async def health_check():
    return {"status": "ok"}

@app.get("/api/online-users")
async def get_online_users():
    """Get list of currently online users"""
    return {"online_users": manager.get_online_users()}

@app.get("/api/chat-history/{sender_id}/{receiver_id}")
async def fetch_chat_history(sender_id: int, receiver_id: int, limit: int = Query(50)):
    """Fetch chat history between two users"""
    messages = await get_chat_history(sender_id, receiver_id, limit)
    return {"messages": messages}

@app.post("/api/announcements")
async def create_announcement(announcement: Announcement):
    """Create new announcement (admin only)"""
    # TODO: Verify sender_id is admin
    announcement_id = await save_announcement(
        announcement.title,
        announcement.description,
        announcement.target_role,
        announcement.sender_id
    )
    
    if announcement_id:
        # Broadcast to connected users
        message = {
            "type": "announcement",
            "announcement_id": announcement_id,
            "title": announcement.title,
            "description": announcement.description,
            "created_on": datetime.now().isoformat()
        }
        await manager.broadcast_announcement(message, announcement.target_role)
        return {"status": "success", "announcement_id": announcement_id}
    else:
        raise HTTPException(status_code=500, detail="Failed to create announcement")

@app.get("/api/announcements/{user_id}")
async def fetch_announcements(user_id: int):
    """Fetch announcements for user"""
    user_role = await get_user_role(user_id)
    announcements = await get_announcements(user_id, user_role)
    return {"announcements": announcements}

@app.post("/api/announcements/{announcement_id}/read/{user_id}")
async def mark_read(announcement_id: int, user_id: int):
    """Mark announcement as read"""
    result = await mark_announcement_read(announcement_id, user_id)
    return {"status": "success" if result else "error"}

@app.websocket("/ws/{user_id}")
async def websocket_endpoint(websocket: WebSocket, user_id: int):
    await manager.connect(user_id, websocket)
    try:
        while True:
            data = await websocket.receive_json()
            
            if data.get('type') == 'chat':
                # Handle private chat message
                sender_id = data.get('sender_id')
                receiver_id = data.get('receiver_id')
                message_text = data.get('message_text')
                
                if sender_id == user_id:  # Verify sender
                    # Save to database
                    message_id = await save_chat_message(sender_id, receiver_id, message_text)
                    
                    # Send to receiver if online
                    response = {
                        "type": "chat",
                        "message_id": message_id,
                        "sender_id": sender_id,
                        "receiver_id": receiver_id,
                        "message_text": message_text,
                        "created_on": datetime.now().isoformat(),
                        "is_read": False
                    }
                    
                    await manager.broadcast_to_user(receiver_id, response)
                    
                    # Echo back to sender
                    await websocket.send_json({**response, "is_read": True, "echo": True})
            
            elif data.get('type') == 'typing':
                # Broadcast typing indicator
                receiver_id = data.get('receiver_id')
                response = {
                    "type": "typing",
                    "sender_id": user_id,
                    "is_typing": data.get('is_typing', True)
                }
                await manager.broadcast_to_user(receiver_id, response)
            
            elif data.get('type') == 'online_status':
                # Broadcast online status
                response = {
                    "type": "online_status",
                    "user_id": user_id,
                    "is_online": True,
                    "online_users": manager.get_online_users()
                }
                # Broadcast to all connected users
                for uid in manager.active_connections:
                    await manager.broadcast_to_user(uid, response)
    
    except WebSocketDisconnect:
        manager.disconnect(user_id)
        # Broadcast offline status
        offline_response = {
            "type": "online_status",
            "user_id": user_id,
            "is_online": False,
            "online_users": manager.get_online_users()
        }
        for uid in manager.active_connections:
            await manager.broadcast_to_user(uid, offline_response)
    
    except Exception as e:
        logger.error(f"WebSocket error for user {user_id}: {e}")
        manager.disconnect(user_id)

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000, reload=False)
