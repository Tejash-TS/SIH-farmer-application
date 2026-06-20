#!/bin/bash

set -e
exec > /var/log/deploy.log 2>&1

echo "Updating server..."
apt update -y

echo "Installing packages..."
apt install -y docker.io git curl unzip

systemctl enable docker
systemctl start docker

echo "Installing kubectl..."
curl -LO "https://dl.k8s.io/release/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl"
chmod +x kubectl
mv kubectl /usr/local/bin/

echo "Installing Kind..."
curl -Lo kind https://kind.sigs.k8s.io/dl/latest/kind-linux-amd64
chmod +x kind
mv kind /usr/local/bin/

echo "Cloning repository..."
cd /root

git clone https://github.com/Tejash-TS/SIH.git

cd /root/SIH/k8s

echo "Creating Kind Cluster..."
kind create cluster --name sih-cluster --config config.yml

kind export kubeconfig --name sih-cluster

echo "Deploying resources..."

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

sleep 60

kubectl get nodes
kubectl get pods -n sih
kubectl get svc -n sih

echo "Deployment completed"