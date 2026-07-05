pipeline {
    agent any

    environment {
        DOCKER_HUB = "tejash727"

        PHP_IMAGE = "tejash727/sih-phpapp"
        CHAT_IMAGE = "tejash727/sih-chatserver"
        AI_IMAGE = "tejash727/sih-aiprediction"

        IMAGE_TAG = "${BUILD_NUMBER}"

        DOCKER_CREDS = "dockerhub"
        KUBECONFIG_CREDENTIAL = "kubeconfig"

        NAMESPACE = "sih"

        EMAIL = "tejashsananse0@gmail.com"
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build PHP Image') {
            steps {
                sh """
                docker build \
                -t ${PHP_IMAGE}:${IMAGE_TAG} \
                -f Dockerfile .
                """
            }
        }

        stage('Build Chat Server Image') {
            steps {
                dir('Chat Server') {
                    sh """
                    docker build \
                    -t ${CHAT_IMAGE}:${IMAGE_TAG} .
                    """
                }
            }
        }

        stage('Build AI Image') {
            steps {
                dir('image prdiction server') {
                    sh """
                    docker build \
                    -t ${AI_IMAGE}:${IMAGE_TAG} .
                    """
                }
            }
        }

        stage('Docker Login') {
            steps {
                withCredentials([usernamePassword(
                    credentialsId: "${DOCKER_CREDS}",
                    usernameVariable: 'USERNAME',
                    passwordVariable: 'PASSWORD'
                )]) {

                    sh '''
                    echo "$PASSWORD" | docker login -u "$USERNAME" --password-stdin
                    '''
                }
            }
        }

        stage('Push Images') {
            steps {

                sh "docker push ${PHP_IMAGE}:${IMAGE_TAG}"

                sh "docker push ${CHAT_IMAGE}:${IMAGE_TAG}"

                sh "docker push ${AI_IMAGE}:${IMAGE_TAG}"

            }
        }

        stage('Deploy to Kubernetes') {

            steps {

                withCredentials([
                    file(credentialsId: "${KUBECONFIG_CREDENTIAL}",
                    variable: 'KUBECONFIG')
                ]) {

                    sh """

                    kubectl set image deployment/phpapp \
                    phpapp=${PHP_IMAGE}:${IMAGE_TAG} \
                    -n ${NAMESPACE}

                    kubectl set image deployment/chatserver \
                    chatserver=${CHAT_IMAGE}:${IMAGE_TAG} \
                    -n ${NAMESPACE}

                    kubectl set image deployment/aiprediction \
                    aiprediction=${AI_IMAGE}:${IMAGE_TAG} \
                    -n ${NAMESPACE}

                    """

                }

            }

        }

        stage('Verify Rollout') {

            steps {

                withCredentials([
                    file(credentialsId: "${KUBECONFIG_CREDENTIAL}",
                    variable: 'KUBECONFIG')
                ]) {

                    sh """

                    kubectl rollout status deployment/phpapp -n ${NAMESPACE}

                    kubectl rollout status deployment/chatserver -n ${NAMESPACE}

                    kubectl rollout status deployment/aiprediction -n ${NAMESPACE}

                    """

                }

            }

        }

        stage('Cleanup') {

            steps {

                sh '''
                docker image prune -f
                '''

            }

        }

    }

    post {

        success {

            mail(
                to: "${EMAIL}",
                subject: "SUCCESS : Build #${BUILD_NUMBER}",
                body: """

Build Successful

Project : SIH Farmer Application

Build Number : ${BUILD_NUMBER}

Images pushed successfully.

Kubernetes deployment completed.

"""

            )

        }

        failure {

            mail(
                to: "${EMAIL}",
                subject: "FAILED : Build #${BUILD_NUMBER}",
                body: """

Build Failed

Project : SIH Farmer Application

Build Number : ${BUILD_NUMBER}

Please check Jenkins console logs.

"""

            )

        }

    }

}
