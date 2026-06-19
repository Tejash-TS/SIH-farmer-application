# 🌾 AgriConnect — AI-Powered Agricultural Platform

> A full-stack, AI-integrated web application for farmers, buyers, vendors, consultants, and admins — deployed on AWS EC2 using Docker & Kubernetes (Kind Cluster).

---

## 📌 Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [System Architecture](#system-architecture)
- [Kubernetes Architecture](#kubernetes-architecture)
- [Deployment Pipeline](#deployment-pipeline)
- [Request Flow](#request-flow)
- [AI Disease Prediction Flow](#ai-disease-prediction-flow)
- [Real-Time Chat Flow](#real-time-chat-flow)
- [Project Structure](#project-structure)
- [Getting Started — Full AWS Deployment Guide](#getting-started--full-aws-deployment-guide)
  - [Step 1 — Launch EC2 Instance](#step-1--launch-ec2-instance)
  - [Step 2 — Connect to EC2](#step-2--connect-to-ec2)
  - [Step 3 — Update the Server](#step-3--update-the-server)
  - [Step 4 — Install Docker](#step-4--install-docker)
  - [Step 5 — Install kubectl](#step-5--install-kubectl)
  - [Step 6 — Install Kind](#step-6--install-kind)
  - [Step 7 — Clone the Project](#step-7--clone-the-project)
  - [Step 8 — Create Kind Cluster](#step-8--create-kind-cluster)
  - [Step 9 — Deploy Kubernetes Resources](#step-9--deploy-kubernetes-resources)
  - [Step 10 — Restore the Database](#step-10--restore-the-database)
  - [Step 11 — Access the Application](#step-11--access-the-application)
- [Database Setup via phpMyAdmin (Manual Way)](#database-setup-via-phpmyadmin-manual-way)
- [Updating Docker Images](#updating-docker-images)
- [Useful Commands](#useful-commands)
- [Terraform Deployment (Automated EC2 Setup)](#terraform-deployment-automated-ec2-setup)
  - [What is Terraform?](#what-is-terraform)
  - [Prerequisites](#prerequisites)
  - [Terraform Project Structure](#terraform-project-structure)
  - [Step 1 — Generate SSH Key Pair](#step-1--generate-ssh-key-pair)
  - [Step 2 — Configure AWS Credentials](#step-2--configure-aws-credentials)
  - [Step 3 — Initialize Terraform](#step-3--initialize-terraform)
  - [Step 4 — Preview Infrastructure](#step-4--preview-infrastructure)
  - [Step 5 — Create Infrastructure](#step-5--create-infrastructure)
  - [Step 6 — Connect to EC2](#step-6--connect-to-ec2)
  - [Step 7 — Verify Automated Deployment](#step-7--verify-automated-deployment)
  - [Step 8 — Access the Application](#step-8--access-the-application)
  - [Destroy Infrastructure](#destroy-infrastructure)
- [Local Setup (Docker Compose)](#local-setup-docker-compose)
- [Environment Variables](#environment-variables)
- [API Endpoints](#api-endpoints)
- [Author](#author)
- [License](#license)

---

## 📖 Overview

**AgriConnect** is a multi-role agricultural web platform that connects the entire agricultural ecosystem — from farmers and buyers to vendors, consultants, and administrators. It leverages **AI-powered disease prediction**, **real-time WebSocket chat**, and a **containerized microservices architecture** deployed on **AWS EC2** using **Docker** and a **Kind Kubernetes cluster**.

This project demonstrates a production-grade deployment pipeline: local development → Dockerized services → Docker Hub → AWS EC2 → Kubernetes orchestration.

---

## ✨ Key Features

- 🔐 **Multi-Role Authentication** — Separate dashboards for Farmers, Buyers, Vendors, Consultants, and Admins
- 🤖 **AI Crop Disease Prediction** — Farmers upload plant images; a TensorFlow + FastAPI service predicts diseases
- 💬 **Real-Time Chat System** — WebSocket-based bidirectional messaging between users
- 🐳 **Dockerized Microservices** — Each service (PHP, Chat, AI, MySQL) runs in its own container
- ☸️ **Kubernetes Orchestration** — Kind cluster with 3 worker nodes, persistent volumes, and service discovery
- ☁️ **AWS EC2 Deployment** — Entire stack deployed and accessible on a live EC2 instance
- 🗄️ **phpMyAdmin** — Web-based MySQL management panel included in the cluster

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| **Frontend** | PHP (with HTML/CSS/JS) |
| **Backend** | PHP (Apache), Python (FastAPI) |
| **AI / ML** | TensorFlow, FastAPI |
| **Real-Time** | Python WebSocket Server |
| **Database** | MySQL 8 |
| **DB Admin** | phpMyAdmin |
| **Containerization** | Docker, Docker Compose |
| **Orchestration** | Kubernetes (Kind Cluster) |
| **Cloud** | AWS EC2 (Ubuntu) |
| **CI/CD** | GitHub → Docker Hub → EC2 |

---

## 🏗️ System Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                          USERS                               │
│                                                              │
│     Farmers | Buyers | Vendors | Consultants | Admin        │
└──────────────────────┬───────────────────────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────────────────────┐
│                 PHP APPLICATION (Apache)                     │
│                                                              │
│    Frontend + Backend Logic + Authentication + Dashboard     │
└──────────────┬──────────────────┬────────────────────────────┘
               │                  │
               ▼                  ▼
     ┌─────────────────┐   ┌─────────────────┐
     │   Chat Server   │   │  AI Prediction  │
     │   Python        │   │  FastAPI        │
     │   WebSocket     │   │  TensorFlow     │
     │   Port 8000     │   │  Port 8001      │
     └────────┬────────┘   └────────┬────────┘
              │                     │
              └──────────┬──────────┘
                         │
                         ▼
            ┌──────────────────────┐
            │    MySQL Database    │
            │      Port 3306       │
            └──────────────────────┘
```

### Component Responsibilities

| Component | Role | Port |
|---|---|---|
| PHP Application | Frontend UI, auth, routing, dashboard logic | 80 |
| Chat Server | Python WebSocket server for real-time messaging | 8000 |
| AI Prediction API | FastAPI + TensorFlow disease prediction service | 8001 |
| MySQL | Persistent data storage for all services | 3306 |
| phpMyAdmin | Web-based DB administration panel | 8080 |

---

## ☸️ Kubernetes Architecture

```
                     Kind Cluster

 ┌────────────────────────────────────────────┐
 │              Control Plane                 │
 └────────────────────────────────────────────┘
                      │
      ┌───────────────┼───────────────┐
      │               │               │
      ▼               ▼               ▼
 ┌──────────┐   ┌──────────┐   ┌──────────┐
 │ Worker 1 │   │ Worker 2 │   │ Worker 3 │
 └──────────┘   └──────────┘   └──────────┘

 Pods distributed across workers:

 ┌──────────────────────────────────────────┐
 │        PHP Application Pods              │
 └──────────────────────────────────────────┘
 ┌──────────────────────────────────────────┐
 │        Chat Server Pods                  │
 └──────────────────────────────────────────┘
 ┌──────────────────────────────────────────┐
 │        AI Prediction Pods                │
 └──────────────────────────────────────────┘
 ┌──────────────────────────────────────────┐
 │        MySQL Pod + Persistent Volume     │
 └──────────────────────────────────────────┘
 ┌──────────────────────────────────────────┐
 │        phpMyAdmin Pod                    │
 └──────────────────────────────────────────┘
```

### Kubernetes Resources Used

| Resource | Purpose |
|---|---|
| `Deployment` | Manages PHP, Chat, and AI service pods |
| `StatefulSet` | Manages MySQL with stable network identity |
| `PersistentVolume` | Persistent storage for MySQL data |
| `PersistentVolumeClaim` | Claims storage for the MySQL pod |
| `Service` | Internal cluster networking between pods |
| `NodePort / LoadBalancer` | Exposes the PHP app to external users |
| `ConfigMap` | Stores environment configuration |
| `Secret` | Stores DB credentials securely |

---

## 🚀 Deployment Pipeline

```
Developer (Local Machine)
         │
         │  git push
         ▼
  GitHub Repository
         │
         │  docker build
         ▼
     Docker Build
         │
         │  docker push
         ▼
      Docker Hub
         │
         │  Pull on EC2
         ▼
      AWS EC2
         │
         │  docker / kind
         ▼
  Kind Kubernetes Cluster
         │
         │  kubectl apply
         ▼
    Pods & Services
         │
         ▼
      End Users
```

### Deployment Steps Summary

1. **Code** — Develop locally, test with Docker Compose
2. **Build** — `docker build` each service image
3. **Push** — Push images to Docker Hub
4. **SSH into EC2** — Connect to the AWS EC2 instance
5. **Pull & Deploy** — Pull images and apply Kubernetes manifests using `kubectl`
6. **Verify** — Check pod status and access the application

---

## 🔄 Request Flow

```
User Request
      │
      ▼
PHP Application (Apache)
      │
      ▼
Kubernetes Service (ClusterIP / NodePort)
      │
      ▼
Application Pod
      │
      ├──────────────► MySQL (Port 3306)
      │                  Data read/write
      │
      ├──────────────► Chat Server (Port 8000)
      │                  Real-time messaging
      │
      └──────────────► AI Prediction API (Port 8001)
                         Disease classification
      │
      ▼
Response Assembled by PHP
      │
      ▼
User (Browser)
```

---

## 🤖 AI Disease Prediction Flow

```
Farmer Uploads Crop Image
           │
           ▼
  PHP Application
  (Receives image, sends to API)
           │
           ▼
  FastAPI Service (Port 8001)
  (Validates & preprocesses image)
           │
           ▼
  TensorFlow Model
  (CNN-based disease classifier)
           │
           ▼
  Disease Prediction Result
  (e.g., "Leaf Blight — 92% confidence")
           │
           ▼
  MySQL Storage
  (Result saved with timestamp & farmer ID)
           │
           ▼
  Display Result to Farmer
  (Via PHP dashboard)
```

---

## 💬 Real-Time Chat Flow

```
User A (Sender)
      │
      │  WebSocket Connect
      ▼
 Chat Server (Python, Port 8000)
      │
      │  Persist Message
      ▼
   MySQL Database
      │
      │  Forward Message
      ▼
 Chat Server
      │
      │  WebSocket Push
      ▼
User B (Receiver)
```

---

## 📁 Project Structure

```
agriConnect/
├── php-app/                    # PHP frontend + backend
│   ├── index.php
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── dashboard/
│   │   ├── farmer.php
│   │   ├── buyer.php
│   │   ├── vendor.php
│   │   ├── consultant.php
│   │   └── admin.php
│   └── Dockerfile
│
├── chat-server/                # Python WebSocket chat server
│   ├── server.py
│   ├── requirements.txt
│   └── Dockerfile
│
├── ai-service/                 # FastAPI + TensorFlow AI service
│   ├── main.py
│   ├── model/
│   │   └── disease_model.h5
│   ├── requirements.txt
│   └── Dockerfile
│
├── database/                   # MySQL init scripts
│   └── init.sql
│
├── k8s/                        # Kubernetes manifests
│   ├── php-deployment.yaml
│   ├── chat-deployment.yaml
│   ├── ai-deployment.yaml
│   ├── mysql-statefulset.yaml
│   ├── mysql-pvc.yaml
│   ├── phpmyadmin-deployment.yaml
│   └── services.yaml
│
├── docker-compose.yml          # Local development setup
└── README.md
```

---

## ⚙️ Getting Started — Full AWS Deployment Guide

> 🟢 **Beginner Friendly** — Follow each step in order. Every command is explained so you know *what* you are doing and *why*.

---

### Step 1 — Launch EC2 Instance

> An EC2 instance is basically a virtual computer (server) rented from Amazon's cloud. Think of it as your own Linux PC running somewhere on the internet.

1. Log in to [AWS Console](https://console.aws.amazon.com/)
2. Go to **EC2 → Launch Instance**
3. Choose the following settings:

| Setting | Value |
|---|---|
| **OS** | Ubuntu 24.04 LTS |
| **Instance Type** | `m7i-flex.large` (2 vCPUs, 8 GB RAM — powered by Intel Xeon Sapphire Rapids, great for Kind + TensorFlow) |
| **Storage** | 30 GB |

4. Under **Security Group**, open these ports (so the internet can reach your app):

| Type | Port | Who Can Access |
|---|---|---|
| SSH | 22 | Your IP (to connect via terminal) |
| HTTP | 80 | 0.0.0.0/0 (everyone — main website) |
| Custom TCP | 8000 | 0.0.0.0/0 (Chat Server) |
| Custom TCP | 9090 | 0.0.0.0/0 (phpMyAdmin panel) |

5. Create or select a `.pem` key pair — **download and save it safely** (you need it to SSH in)

---

### Step 2 — Connect to EC2

> SSH lets you control your EC2 server from your local terminal (or Windows CMD/PowerShell).

From your local machine (Windows/Mac/Linux):

```bash
ssh -i "BE.pem" ubuntu@<YOUR-EC2-PUBLIC-IP>
```

**Example:**
```bash
ssh -i "BE.pem" ubuntu@65.0.134.143
```

> 💡 Replace `<YOUR-EC2-PUBLIC-IP>` with the actual IP shown on your EC2 dashboard.
> If you get a "permission denied" error on Windows, right-click the `.pem` file → Properties → Security → make sure only your user has access.

---

### Step 3 — Update the Server

> Always update your server first to get the latest security patches and package lists.

```bash
sudo apt update

sudo apt upgrade -y
```

> 💡 `sudo` means "run as administrator". `apt` is Ubuntu's package manager (like an app store for Linux).

---

### Step 4 — Install Docker

> Docker lets you run your application inside containers — isolated boxes that have everything the app needs to run. This way, it works the same on every machine.

```bash
# Install Docker
sudo apt install docker.io -y

# Start Docker automatically when server restarts
sudo systemctl enable docker

# Start Docker right now
sudo systemctl start docker
```

**Check it worked:**
```bash
docker --version
# Expected output: Docker version 24.x.x, build ...
```

**Add your user to the Docker group** (so you don't need `sudo` every time):
```bash
sudo usermod -aG docker ubuntu

newgrp docker
```

> 💡 `usermod -aG docker ubuntu` adds the `ubuntu` user to the `docker` group so it can run Docker commands without `sudo`.

---

### Step 5 — Install kubectl

> `kubectl` is the command-line tool to control your Kubernetes cluster. Think of it as the "remote control" for Kubernetes.

```bash
# Download kubectl
curl -LO "https://dl.k8s.io/release/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl"

# Make it executable
chmod +x kubectl

# Move it to a system-wide location so you can run it from anywhere
sudo mv kubectl /usr/local/bin/
```

**Check it worked:**
```bash
kubectl version --client
# Expected output: Client Version: v1.xx.x
```

---

### Step 6 — Install Kind

> Kind = **K**ubernetes **IN** **D**ocker. It creates a real Kubernetes cluster that runs inside Docker containers on your EC2 instance. This is much lighter than a full production cluster.

```bash
# Download Kind
curl -Lo ./kind https://kind.sigs.k8s.io/dl/latest/kind-linux-amd64

# Make it executable
chmod +x ./kind

# Move it system-wide
sudo mv ./kind /usr/local/bin/kind
```

**Check it worked:**
```bash
kind version
# Expected output: kind v0.xx.x go1.xx linux/amd64
```

---

### Step 7 — Clone the Project

> Git clone downloads your project code from GitHub onto the EC2 server.

```bash
git clone https://github.com/Tejash-TS/SIH.git

cd SIH
```

**Project structure you'll see:**
```
SIH/
│
├── docker-compose.yml
├── Dockerfile
│
├── Chat Server/
├── image prdiction server/
├── Db_backup_files/
│
└── k8s/
    ├── namespace.yaml
    ├── mysql-secret.yaml
    ├── mysql-pv.yaml
    ├── mysql-pvc.yaml
    ├── mysql-deployment.yaml
    ├── mysql-service.yaml
    ├── phpapp-deployment.yaml
    ├── phpapp-service.yaml
    ├── chatserver-deployment.yaml
    ├── chatserver-service.yaml
    ├── aiprediction-deployment.yaml
    ├── aiprediction-service.yaml
    ├── phpmyadmin-deployment.yaml
    ├── phpmyadmin-service.yaml
    └── config.yml
```

---

### Step 8 — Create Kind Cluster

> This step creates your Kubernetes cluster with 1 control-plane node and 3 worker nodes inside Docker. The `extraPortMappings` forwards EC2 ports to your pods.

The `config.yml` file is already in `k8s/`. It looks like this:

```yaml
kind: Cluster
apiVersion: kind.x-k8s.io/v1alpha4

nodes:
- role: control-plane
  extraPortMappings:

  - containerPort: 30080   # PHP app inside cluster
    hostPort: 80            # accessible on EC2 port 80
    protocol: TCP

  - containerPort: 30090   # phpMyAdmin inside cluster
    hostPort: 9090          # accessible on EC2 port 9090
    protocol: TCP

  - containerPort: 30000   # Chat server inside cluster
    hostPort: 8000          # accessible on EC2 port 8000
    protocol: TCP

- role: worker
- role: worker
- role: worker
```

**Create the cluster:**
```bash
cd ~/SIH/k8s

kind create cluster \
  --name sih-cluster \
  --config config.yml
```

> ⏳ This takes 2–3 minutes. Kind is spinning up 4 Docker containers (1 control-plane + 3 workers) to form your cluster.

**Verify the cluster exists:**
```bash
kind get clusters
# Expected output: sih-cluster
```

---

### Step 9 — Deploy Kubernetes Resources

> `kubectl apply -f <file>` reads the YAML file and tells Kubernetes to create those resources (pods, services, volumes, etc.) in the cluster.

**Run these commands one by one, in this exact order:**

```bash
# 1. Create a namespace (a logical group for all our app's resources)
kubectl apply -f namespace.yaml

# 2. Create the DB password secret (stored securely in Kubernetes)
kubectl apply -f mysql-secret.yaml

# 3. Create the storage volume for MySQL data
kubectl apply -f mysql-pv.yaml
kubectl apply -f mysql-pvc.yaml

# 4. Deploy MySQL database
kubectl apply -f mysql-deployment.yaml
kubectl apply -f mysql-service.yaml

# 5. Deploy the PHP web app
kubectl apply -f phpapp-deployment.yaml
kubectl apply -f phpapp-service.yaml

# 6. Deploy the Chat Server
kubectl apply -f chatserver-deployment.yaml
kubectl apply -f chatserver-service.yaml

# 7. Deploy the AI Prediction service
kubectl apply -f aiprediction-deployment.yaml
kubectl apply -f aiprediction-service.yaml

# 8. Deploy phpMyAdmin (DB admin panel)
kubectl apply -f phpmyadmin-deployment.yaml
kubectl apply -f phpmyadmin-service.yaml
```

**Verify all pods are running:**
```bash
kubectl get pods -n sih
```

Expected output:
```
NAME                            READY   STATUS    RESTARTS   AGE
phpapp-xxxxxxx                  1/1     Running   0          2m
mysql-xxxxxxx                   1/1     Running   0          3m
chatserver-xxxxxxx              1/1     Running   0          2m
aiprediction-xxxxxxx            1/1     Running   0          2m
phpmyadmin-xxxxxxx              1/1     Running   0          1m
```

> 💡 If any pod shows `Pending` or `CrashLoopBackOff`, check its logs with `kubectl logs <pod-name> -n sih`

**Verify services:**
```bash
kubectl get svc -n sih
```

---

### Step 10 — Restore the Database

> The project includes a MySQL backup file. We need to copy it into the MySQL pod and import it.

**Find your MySQL pod name first:**
```bash
kubectl get pods -n sih
# Look for the pod starting with "mysql-"
```

**Copy the backup file into the MySQL pod:**
```bash
kubectl cp \
  ../Db_backup_files/sih_03-05-2026.sql \
  sih/<mysql-pod-name>:/tmp/sih.sql
```

**Example** (replace with your actual pod name):
```bash
kubectl cp ../Db_backup_files/sih_03-05-2026.sql sih/mysql-6d7f9b8c4-xk2p9:/tmp/sih.sql
```

**Enter the MySQL pod and import the database:**
```bash
# Open a shell inside the MySQL pod
kubectl exec -it <mysql-pod-name> -n sih -- bash

# Login to MySQL
mysql -uroot -proot

# Create the database
CREATE DATABASE sih;

# Exit MySQL
exit

# Import the backup
mysql -uroot -proot sih < /tmp/sih.sql
```

**Verify the import worked:**
```bash
mysql -uroot -proot

SHOW DATABASES;
# You should see "sih" in the list

USE sih;

SHOW TABLES;
# You should see all your project tables

exit
```

**Exit the pod shell:**
```bash
exit
```

---

### Step 11 — Access the Application

Your app is now live! Open a browser and visit:

| Service | URL |
|---|---|
| 🌾 **Main Website** | `http://<YOUR-EC2-PUBLIC-IP>` |
| 💬 **Chat Server** | `http://<YOUR-EC2-PUBLIC-IP>:8000` |
| 🗄️ **phpMyAdmin** | `http://<YOUR-EC2-PUBLIC-IP>:9090` |

**phpMyAdmin Login Details:**

| Field | Value |
|---|---|
| Server | `mysql` |
| Username | `root` |
| Password | `root` |
| Database | `sih` |

---

## 🗄️ Database Setup via phpMyAdmin (Manual Way)

> Instead of using terminal commands inside the pod, you can manage the database visually through the phpMyAdmin web panel on port **9090**. This is the easiest way for beginners.

---

### Step 1 — Open phpMyAdmin

Open your browser and go to:

```
http://<YOUR-EC2-PUBLIC-IP>:9090
```

**Login with:**

| Field | Value |
|---|---|
| Server | `mysql` |
| Username | `root` |
| Password | `root` |

Click **Go** to login.

---

### Step 2 — Delete the Existing `sih` Database

> We delete the old database first to start completely fresh before importing.

1. In the **left sidebar**, click on **`sih`** database
2. In the top menu, click **Operations**
3. Scroll down to the **"Drop the database"** section
4. Click **Drop Database**
5. A confirmation popup appears — click **OK**

The `sih` database is now deleted.

---

### Step 3 — Create a New `sih` Database

1. In the left sidebar, click **New** (at the very top left)
2. In the **Database name** field, type: `sih`
3. In the **Collation / Character set** dropdown next to it, select:

```
utf8mb4_general_ci
```

> 💡 `utf8mb4_general_ci` means:
> - `utf8mb4` — supports all characters including emojis and special symbols (better than plain `utf8`)
> - `general_ci` — **CI** = Case Insensitive (so `Name` and `name` are treated the same)
> - This is the most commonly used collation for web apps — safe default choice

4. Click **Create**

The new empty `sih` database appears in the left sidebar.

---

### Step 4 — Import SQL File from Your Local Machine

> Now we load the actual database tables and data from your backup `.sql` file.

1. Make sure **`sih`** is selected in the left sidebar (click it if not)
2. In the top menu, click **Import**
3. Under **"File to import"**, click **Choose File**
4. Browse your local computer and select your backup file:
   ```
   sih_03-05-2026.sql
   ```
   (or whichever `.sql` file is in your `Db_backup_files/` folder)
5. Leave all other settings as default
6. Scroll to the bottom and click **Import**

> ⏳ Wait for the import to finish — this may take 10–30 seconds depending on database size.

**Success message:**
```
Import has been successfully finished, X queries executed.
```

---

### Step 5 — Verify the Import

1. In the left sidebar, click **`sih`**
2. You should see all your tables listed (users, products, chats, predictions, etc.)
3. Click any table → click **Browse** to confirm data is present

> ✅ Your database is ready. The PHP application will automatically connect to it since it's already configured to use the `sih` database on the `mysql` service inside the cluster.

---

## 🔁 Updating Docker Images

> When you change your code locally, you need to rebuild the Docker image, push it to Docker Hub, and restart the Kubernetes pods to pick up the new version.

**PHP App:**
```bash
docker build -t tejash727/sih-phpapp:latest .
docker push tejash727/sih-phpapp:latest
```

**Chat Server:**
```bash
docker build -t tejash727/sih-chatserver:latest .
docker push tejash727/sih-chatserver:latest
```

**AI Prediction Service:**
```bash
docker build -t tejash727/sih-aiprediction:latest .
docker push tejash727/sih-aiprediction:latest
```

**Restart pods to pull the new images:**
```bash
kubectl rollout restart deployment phpapp -n sih
kubectl rollout restart deployment chatserver -n sih
kubectl rollout restart deployment aiprediction -n sih
```

> 💡 `rollout restart` gracefully replaces old pods with new ones — no downtime!

---

## 🧰 Useful Commands

**Check pod status:**
```bash
kubectl get pods -n sih
```

**View logs for a service (great for debugging):**
```bash
kubectl logs deployment/phpapp -n sih
kubectl logs deployment/chatserver -n sih
kubectl logs deployment/mysql -n sih
```

**Check services and their ports:**
```bash
kubectl get svc -n sih
```

**Restart a specific deployment:**
```bash
kubectl rollout restart deployment phpapp -n sih
```

**Delete the entire Kind cluster (to start fresh):**
```bash
kind delete cluster --name sih-cluster
```

> ⚠️ This deletes all pods and data inside the cluster. The MySQL data on the PersistentVolume may also be lost unless backed up.

---

## 🚀 Terraform Deployment (Automated EC2 Setup)

> 🟢 **Beginner Friendly** — Instead of manually clicking through the AWS Console and typing 20+ commands on EC2, Terraform does it all for you in one command. Think of it as "infrastructure as code" — you describe what you want, and Terraform creates it automatically.

---

### What is Terraform?

> **Terraform** is an open-source tool by HashiCorp that lets you define cloud infrastructure (EC2, security groups, key pairs, etc.) in simple config files, then create or destroy it all with a single command.
>
> Instead of: AWS Console → click → click → SSH → install Docker → install Kind → clone repo...
> With Terraform: `terraform apply` → ☕ wait → your entire server is ready!

---

### Prerequisites

Install the following tools on your **local machine** (not EC2):

**1. AWS CLI** — lets your terminal talk to AWS
```bash
# Ubuntu/Debian
sudo apt install awscli -y

# Verify
aws --version
```

**2. Terraform** — the infrastructure automation tool
```bash
# Ubuntu/Debian
sudo apt-get update && sudo apt-get install -y gnupg software-properties-common

wget -O- https://apt.releases.hashicorp.com/gpg | gpg --dearmor | \
  sudo tee /usr/share/keyrings/hashicorp-archive-keyring.gpg

echo "deb [signed-by=/usr/share/keyrings/hashicorp-archive-keyring.gpg] \
  https://apt.releases.hashicorp.com $(lsb_release -cs) main" | \
  sudo tee /etc/apt/sources.list.d/hashicorp.list

sudo apt update && sudo apt install terraform -y

# Verify
terraform --version
```

**3. SSH** — to connect to EC2 after provisioning (pre-installed on Mac/Linux; use Git Bash on Windows)

---

### Terraform Project Structure

```
terraform/
│
├── provider.tf          # Tells Terraform to use AWS, and which region
├── variables.tf         # Reusable variables (region, instance type, AMI, etc.)
├── ec2.tf               # EC2 instance, security group, key pair, EBS volume
├── outputs.tf           # Prints EC2 public IP/DNS after apply
├── deploy.sh            # Auto-run script on EC2 at first boot (installs everything)
├── terra_key_ec2        # Private SSH key (NEVER commit to GitHub)
└── terra_key_ec2.pub    # Public SSH key (uploaded to AWS as a Key Pair)
```

**What each file does:**

| File | Purpose |
|---|---|
| `provider.tf` | Sets AWS as the cloud provider and the region (`ap-south-1`) |
| `variables.tf` | Defines variables like AMI ID, instance type, key name so you can change them easily |
| `ec2.tf` | The main file — creates EC2 instance, security group with ports, and attaches the SSH key |
| `outputs.tf` | After creation, prints the EC2 public IP and DNS to your terminal |
| `deploy.sh` | Shell script that runs automatically on first EC2 boot via `user_data` — installs Docker, kubectl, Kind, clones repo, and deploys the full Kubernetes stack |

---

### Step 1 — Generate SSH Key Pair

> SSH keys are how you securely log into EC2 without a password. You keep the private key (`terra_key_ec2`) locally; the public key (`terra_key_ec2.pub`) goes to AWS.

Run this inside the `terraform/` folder:

```bash
cd terraform/

ssh-keygen -t rsa -b 4096 -f terra_key_ec2
```

When prompted for a passphrase — press **Enter** twice to skip (easier for automation).

Two files are created:
```
terra_key_ec2        ← Private key (keep this safe, never share or commit!)
terra_key_ec2.pub    ← Public key (Terraform uploads this to AWS)
```

> ⚠️ Add `terra_key_ec2` to your `.gitignore` immediately:
> ```bash
> echo "terra_key_ec2" >> ../.gitignore
> ```

---

### Step 2 — Configure AWS Credentials

> Terraform needs permission to create resources in your AWS account. `aws configure` saves your credentials locally so Terraform can use them.

```bash
aws configure
```

Enter when prompted:

```
AWS Access Key ID     : <your-access-key-id>
AWS Secret Access Key : <your-secret-access-key>
Default region name   : ap-south-1
Default output format : json
```

> 💡 **Where to get your keys:**
> AWS Console → Top right (your name) → Security credentials → Access keys → Create access key

Credentials are saved at `~/.aws/credentials` on your machine — Terraform reads them automatically.

---

### Step 3 — Initialize Terraform

> `terraform init` downloads the AWS provider plugin (like installing npm packages — done only once).

```bash
terraform init
```

Expected output:
```
Terraform has been successfully initialized!
```

**Validate your configuration files for syntax errors:**
```bash
terraform validate
```

Expected output:
```
Success! The configuration is valid.
```

---

### Step 4 — Preview Infrastructure

> Before creating anything, `terraform plan` shows you exactly what will be created — like a dry run. Nothing is built yet.

```bash
terraform plan
```

You'll see a detailed list of resources Terraform will create:

```
+ aws_instance.sih_ec2              → m7i-flex.large EC2 instance
+ aws_security_group.sih_sg         → Security group with ports 22, 80, 8000, 9090, 30000-30090
+ aws_key_pair.sih_keypair          → SSH key pair from terra_key_ec2.pub
+ aws_ebs_volume (root block)       → 30 GB GP3 storage
```

> 💡 Review this output carefully before applying. If something looks wrong, fix the `.tf` files first.

---

### Step 5 — Create Infrastructure

> This is the main command — Terraform creates everything on AWS automatically. The `-auto-approve` flag skips the manual "yes" confirmation.

```bash
terraform apply -auto-approve
```

Terraform will create:

| Resource | Details |
|---|---|
| **EC2 Instance** | `m7i-flex.large`, Ubuntu 24.04 LTS, `ap-south-1` |
| **Security Group** | Opens ports 22, 80, 8000, 9090, 30000–30090 |
| **AWS Key Pair** | Uploads your `terra_key_ec2.pub` to AWS |
| **Root EBS Volume** | 30 GB GP3 (faster than GP2, same price) |

> ⏳ This takes **3–5 minutes**. EC2 launches, then `deploy.sh` runs automatically in the background on first boot — installing Docker, kubectl, Kind, cloning the repo, and deploying all Kubernetes manifests.

After completion, Terraform prints:

```
Apply complete! Resources: 4 added, 0 changed, 0 destroyed.

Outputs:
ec2_public_dns = ec2-xx-xx-xx-xx.ap-south-1.compute.amazonaws.com
ec2_public_ip  = 65.0.xxx.xxx
```

> 💡 Save the public IP/DNS — you'll need it to access the app and SSH in.

---

### Step 6 — Connect to EC2

> The EC2 is ready. SSH in to check the deployment status.

First, get the output again if you forgot:
```bash
terraform output
```

Then connect:
```bash
ssh -i terra_key_ec2 ubuntu@<EC2_PUBLIC_DNS>
```

**Example:**
```bash
ssh -i terra_key_ec2 ubuntu@ec2-65-0-134-143.ap-south-1.compute.amazonaws.com
```

> 💡 If you get "Permission denied (publickey)", make sure the key file permissions are correct:
> ```bash
> chmod 400 terra_key_ec2
> ```

---

### Step 7 — Verify Automated Deployment

> The `deploy.sh` script ran automatically when EC2 booted. Let's verify everything is up and running.

**Check Kind cluster exists:**
```bash
kind get clusters
# Expected: sih-cluster
```

**Check Kubernetes nodes:**
```bash
kubectl get nodes
# Expected: 1 control-plane + 3 workers in Ready state
```

**Check all pods are running:**
```bash
kubectl get pods -n sih
```

Expected output:
```
NAME                            READY   STATUS    RESTARTS   AGE
phpapp-xxxxxxx                  1/1     Running   0          5m
mysql-xxxxxxx                   1/1     Running   0          5m
chatserver-xxxxxxx              1/1     Running   0          5m
aiprediction-xxxxxxx            1/1     Running   0          5m
phpmyadmin-xxxxxxx              1/1     Running   0          5m
```

**Check services:**
```bash
kubectl get svc -n sih
```

> 💡 If pods show `Pending` or `CrashLoopBackOff`, the `deploy.sh` script may still be running in the background. Wait 2–3 minutes and check again:
> ```bash
> # Watch the cloud-init boot log to see deploy.sh progress
> sudo tail -f /var/log/cloud-init-output.log
> ```

**Repository is cloned at:**
```
/root/SIH-farmer-application/
```

---

### Step 8 — Access the Application

Your app is live! Open a browser and visit:

| Service | URL |
|---|---|
| 🌾 **Main Website** | `http://<EC2_PUBLIC_IP>` |
| 💬 **Chat Server** | `http://<EC2_PUBLIC_IP>:8000` |
| 🗄️ **phpMyAdmin** | `http://<EC2_PUBLIC_IP>:9090` |

**phpMyAdmin Login:**

| Field | Value |
|---|---|
| Server | `mysql` |
| Username | `root` |
| Password | `root` |
| Database | `sih` |

**Security Group Ports Summary:**

| Port | Purpose |
|---|---|
| 22 | SSH access |
| 80 | PHP Web Application |
| 8000 | Chat WebSocket Server |
| 9090 | phpMyAdmin Panel |
| 30000–30090 | Kubernetes NodePort range |

---

### Destroy Infrastructure

> When you're done testing and want to avoid AWS charges, destroy all resources with one command.

```bash
terraform destroy -auto-approve
```

This permanently deletes:

| Resource | What's Removed |
|---|---|
| EC2 Instance | The virtual server and everything running on it |
| Security Group | All firewall rules |
| AWS Key Pair | The uploaded public key |
| EBS Volume | The 30 GB root disk and all data on it |

> ⚠️ **Important:** Take a database backup BEFORE destroying if you need the data:
> ```bash
> kubectl exec -it <mysql-pod-name> -n sih -- mysqldump -uroot -proot sih > backup_$(date +%F).sql
> ```
> Then copy it to your local machine:
> ```bash
> scp -i terra_key_ec2 ubuntu@<EC2_PUBLIC_IP>:~/backup_*.sql ./
> ```

Expected output:
```
Destroy complete! Resources: 4 destroyed.
```

> 💡 **Stop vs Destroy:** If you just want to pause (keep the data, resume later), go to AWS Console → EC2 → **Stop** the instance instead. Stopped instances are not charged for compute time, only for the EBS storage (~₹12/month for 30 GB).

---

## 💻 Local Setup (Docker Compose)

> For quick local testing without Kubernetes, use Docker Compose.

```bash
# Clone the repo
git clone https://github.com/Tejash-TS/SIH.git
cd SIH

# Start all services
docker-compose up --build

# Access locally
# PHP App:     http://localhost:80
# Chat Server: ws://localhost:8000
# phpMyAdmin:  http://localhost:9090
```

---

## 🔐 Environment Variables

Create a `.env` file in the root directory. **Never commit this file to GitHub.**

```env
# Database
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=sih
MYSQL_USER=root
MYSQL_PASSWORD=root

# PHP App
DB_HOST=mysql
DB_PORT=3306
DB_NAME=sih
DB_USER=root
DB_PASS=root

# AI Service
AI_SERVICE_URL=http://aiprediction:8001

# Chat Server
CHAT_SERVER_PORT=8000
```

---

## 📡 API Endpoints

### AI Prediction Service (FastAPI — Port 8001)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/` | Health check |
| `POST` | `/predict` | Upload image, get disease prediction |
| `GET` | `/docs` | Swagger API documentation |

### Chat Server (WebSocket — Port 8000)

| Event | Direction | Description |
|---|---|---|
| `connect` | Client → Server | Establish WebSocket connection |
| `send_message` | Client → Server | Send a chat message |
| `receive_message` | Server → Client | Receive a message in real-time |
| `disconnect` | Client → Server | Close the connection |

---

## 👤 User Roles

| Role | Capabilities |
|---|---|
| **Farmer** | Upload crop images for disease detection, chat with consultants, list products |
| **Buyer** | Browse and purchase agricultural products |
| **Vendor** | Manage product listings and orders |
| **Consultant** | Provide farming advice via chat |
| **Admin** | Full platform management, user control, analytics |

---

## 🙋 Author

**Tejash S.**
- 💼 Final Year B.E. Computer Engineering (2026 Batch)
- 🔗 [GitHub](https://github.com/Tejash-TS)
- 🔗 [LinkedIn](https://www.linkedin.com/in/tejash-s)

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

> ⭐ If you found this project helpful or interesting, please consider giving it a star on GitHub!
