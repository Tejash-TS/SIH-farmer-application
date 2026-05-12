from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.responses import JSONResponse
import tensorflow as tf
import numpy as np
from PIL import Image
import io
import os

app = FastAPI(title="🍇 Grape Disease Detection API")

MODEL_PATH = "grape_model.h5"
ALLOWED_EXTENSIONS = {"png", "jpg", "jpeg", "gif", "bmp"}

CLASS_NAMES = [
    'Bacterial Rot', 'Black Rot', 'Downey Mildew',
    'ESCA', 'Healthy', 'Leaf Blight', 'Powdery Mildew'
]

DISEASE_INFO = {
    'Black Rot': {
        'description': 'Fungal disease causing dark lesions and fruit rot',
        'severity': 'High',
        'treatment': 'Apply fungicides and remove infected plant material',
        'color': '#f44336'
    },
    'ESCA': {
        'description': 'Wood disease causing leaf discoloration and vine decline',
        'severity': 'High',
        'treatment': 'Professional treatment required, may need vine removal',
        'color': '#ff9800'
    },
    'Healthy': {
        'description': 'No signs of disease detected',
        'severity': 'None',
        'treatment': 'Continue regular maintenance and monitoring',
        'color': '#4CAF50'
    },
    'Leaf Blight': {
        'description': 'Bacterial infection causing leaf spots and defoliation',
        'severity': 'Medium',
        'treatment': 'Apply copper-based fungicides and improve air circulation',
        'color': '#9c27b0'
    },
    'Downey Mildew': {
        'description': 'Bacterial infection causing leaf spots and defoliation',
        'severity': 'Medium',
        'treatment': 'Apply copper-based fungicides and improve air circulation',
        'color': '#9c27b0'
    },
    'Powdery Mildew': {
        'description': 'Bacterial infection causing leaf spots and defoliation',
        'severity': 'Medium',
        'treatment': 'Apply copper-based fungicides and improve air circulation',
        'color': '#9c27b0'
    },
    'Bacterial Rot': {
        'description': 'Bacterial infection causing leaf spots and defoliation',
        'severity': 'Medium',
        'treatment': 'Apply copper-based fungicides and improve air circulation',
        'color': '#9c27b0'
    }
}

model = None


def load_model():
    global model
    if os.path.exists(MODEL_PATH):
        model = tf.keras.models.load_model(MODEL_PATH)
        print(f"✅ Model loaded successfully from {MODEL_PATH}")
    else:
        print(f"❌ Model file not found: {MODEL_PATH}")


def preprocess_image(image, target_size=(224, 224)):
    if image.mode != "RGB":
        image = image.convert("RGB")
    image = image.resize(target_size)
    img_array = np.array(image) / 255.0
    img_array = np.expand_dims(img_array, axis=0)
    return img_array


def predict_disease(image: Image.Image):
    if model is None:
        return None, "Model not loaded"

    processed_image = preprocess_image(image)
    predictions = model.predict(processed_image, verbose=0)
    class_probabilities = predictions[0]

    predicted_class_idx = np.argmax(class_probabilities)
    predicted_class = CLASS_NAMES[predicted_class_idx]
    confidence = float(class_probabilities[predicted_class_idx])

    all_predictions = []
    for i, class_name in enumerate(CLASS_NAMES):
        all_predictions.append({
            "class": class_name,
            "confidence": float(class_probabilities[i]),
            "info": DISEASE_INFO[class_name]
        })

    all_predictions.sort(key=lambda x: x["confidence"], reverse=True)

    return {
        "predicted_class": predicted_class,
        "confidence": confidence,
        "all_predictions": all_predictions,
        "disease_info": DISEASE_INFO[predicted_class]
    }, None


@app.get("/")
def root():
    return {"message": "🍇 Welcome to Grape Disease Detection API"}
@app.post("/predict")
async def predict(image: UploadFile = File(...)):
    try:
        ext = image.filename.split(".")[-1].lower()
        if ext not in ALLOWED_EXTENSIONS:
            raise HTTPException(status_code=400, detail="Invalid file type")

        image_data = await image.read()
        pil_image = Image.open(io.BytesIO(image_data))

        result, error = predict_disease(pil_image)

        if error:
            raise HTTPException(status_code=500, detail=error)

        all_diseases = [
            {
                "disease": pred["class"],
                "confidence_percent": f"{pred['confidence'] * 100:.2f}%"
            }
            for pred in result["all_predictions"]
        ]

        return {
            "predicted_disease": result["predicted_class"],
            "confidence_percent": f"{result['confidence'] * 100:.2f}%",
            "all_diseases": all_diseases
        }

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Server error: {str(e)}")


@app.get("/health")
def health_check():
    model_status = "loaded" if model else "not loaded"
    return {
        "status": "healthy",
        "model_status": model_status,
        "classes": CLASS_NAMES
    }


@app.get("/model_info")
def model_info():
    if model is None:
        raise HTTPException(status_code=500, detail="Model not loaded")

    return {
        "model_loaded": True,
        "classes": CLASS_NAMES,
        "disease_info": DISEASE_INFO,
        "input_shape": model.input_shape,
        "output_shape": model.output_shape
    }


@app.on_event("startup")
def startup_event():
    load_model()
