#!/bin/bash

# Update Server
sudo apt update -y
sudo apt upgrade -y

# Install Docker
sudo apt install docker.io git curl -y

sudo systemctl enable docker
sudo systemctl start docker

sudo usermod -aG docker ubuntu

# Install kubectl
curl -LO "https://dl.k8s.io/release/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl"

chmod +x kubectl
sudo mv kubectl /usr/local/bin/

# Install Kind
curl -Lo ./kind https://kind.sigs.k8s.io/dl/latest/kind-linux-amd64

chmod +x kind
sudo mv kind /usr/local/bin/kind

# Verify Installations
docker --version
kubectl version --client
kind version

# Clone Project
git clone https://github.com/Tejash-TS/SIH.git

cd SIH/k8s

# Create Kind Cluster
kind create cluster \
  --name sih-cluster \
  --config config.yml

# Deploy Resources
kubectl apply -f namespace.yaml

kubectl apply -f mysql-secret.yaml

kubectl apply -f mysql-pv.yaml
kubectl apply -f mysql-pvc.yaml

kubectl apply -f mysql-deployment.yaml
kubectl apply -f mysql-service.yaml

kubectl apply -f phpapp-deployment.yaml
kubectl apply -f phpapp-service.yaml

kubectl apply -f chatserver-deployment.yaml
kubectl apply -f chatserver-service.yaml

kubectl apply -f aiprediction-deployment.yaml
kubectl apply -f aiprediction-service.yaml

kubectl apply -f phpmyadmin-deployment.yaml
kubectl apply -f phpmyadmin-service.yaml

echo "Waiting for Pods..."
sleep 60

kubectl get pods -n sih
kubectl get svc -n sih

echo "Deployment Completed Successfully"