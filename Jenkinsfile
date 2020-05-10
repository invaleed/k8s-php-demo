def project = 'demo'
def appName = 'k8s-php'
def imageTag = "harbor.ict.prod/${project}/${appName}:${env.BRANCH_NAME}.${env.BUILD_NUMBER}"

pipeline {
  agent {
    kubernetes {
      label 'nginx-hello'
      defaultContainer 'jnlp'
      yaml """
apiVersion: v1
kind: Pod
metadata:
labels:
  component: ci
spec:
  containers:
  - name: docker
    image: harbor.ict.prod/demo/dind
    securityContext:
      privileged: true    
    tty: true
    command: ["dockerd-entrypoint.sh", "--insecure-registry=harbor.ict.prod"]
  - name: kubectl
    image: harbor.ict.prod/demo/kubectl
    command:
    - cat
    tty: true
"""
}
  }
        stages {

        stage('Clone Repository') {
          steps {
                checkout scm
          }
        }
        stage('Change Parameters') {
          when { branch 'canary' 
          steps {
                sh("sed -i.bak 's#__DB_SERVER__#$DB_SERVER_DEV#' demo/config.php")
                sh("sed -i.bak 's#__DB_USER__#$DB_USER_DEV#' demo/config.php")
                sh("sed -i.bak 's#__DB_PASSWORD__#$DB_PASSWORD_DEV#' demo/config.php")
                sh("sed -i.bak 's#__DB_NAME__#$DB_NAME_DEV#' demo/config.php")
          }
          when { branch 'master' 
          steps {
                sh("sed -i.bak 's#__DB_SERVER__#$DB_SERVER_PROD#' demo/config.php")
                sh("sed -i.bak 's#__DB_USER__#$DB_USER_PROD#' demo/config.php")
                sh("sed -i.bak 's#__DB_PASSWORD__#$DB_PASSWORD_PROD#' demo/config.php")
                sh("sed -i.bak 's#__DB_NAME__#$DB_NAME_PROD#' demo/config.php")
          }
          when { 
                not { branch 'master' } 
                not { branch 'canary' }
          } 
          steps {
                sh("sed -i.bak 's#__DB_SERVER__#$DB_SERVER_DEV#' demo/config.php")
                sh("sed -i.bak 's#__DB_USER__#$DB_USER_DEV#' demo/config.php")
                sh("sed -i.bak 's#__DB_PASSWORD__#$DB_PASSWORD_DEV#' demo/config.php")
                sh("sed -i.bak 's#__DB_NAME__#$DB_NAME_DEV#' demo/config.php")
          }

        }
        stage('Build Docker Images') {
          steps {
                container('docker') {
                  sh "docker build -t ${imageTag} ."
                }
          }
        }
        stage('Push Images') {
          steps {
                container('docker') {
                  sh "docker login -u $USER_HARBOR -p $PASS_HARBOR https://harbor.ict.prod"
                  sh "docker push ${imageTag}"
                }
          }
        }
        stage('Deploy Canary') {
          // Canary branch
          when { branch 'canary' }
          steps {
                container('kubectl') {
                  // Create namespace if it doesn't exist
                  sh("kubectl get ns ${env.BRANCH_NAME} || kubectl create ns ${env.BRANCH_NAME}")
                  // Change deployed image in canary to the one we just built
                  sh("sed -i.bak 's#docker.adzkia.web.id/ramadoni/nginx-hello:latest#${imageTag}#' ./k8s/canary/*.yaml")
                  sh("kubectl --namespace=canary apply -f k8s/services/")
                  sh("kubectl --namespace=canary apply -f k8s/canary/")
                  sh("echo http://`kubectl --namespace=canary get service/${appName} -o jsonpath='{.status.loadBalancer.ingress[0].ip}'` > ${appName}")
                } 
          }
        }
        stage('Deploy Production') {
          // Production branch
          when { branch 'master' }
          steps{
                container('kubectl') {
                // Change deployed image in canary to the one we just built
                  sh("sed -i.bak 's#docker.adzkia.web.id/ramadoni/nginx-hello:latest#${imageTag}#' ./k8s/production/*.yaml")
                  sh("kubectl --namespace=production apply -f k8s/services/")
                  sh("kubectl --namespace=production apply -f k8s/production/")
                  sh("echo http://`kubectl --namespace=production get service/${appName} -o jsonpath='{.status.loadBalancer.ingress[0].ip}'` > ${appName}")
                }
          }
        }
        stage('Deploy Dev') {
          // Developer Branches
          when { 
                not { branch 'master' } 
                not { branch 'canary' }
          } 
          steps {
                container('kubectl') {
                  // Create namespace if it doesn't exist
                  sh("kubectl get ns ${env.BRANCH_NAME} || kubectl create ns ${env.BRANCH_NAME}")
                  // Don't use public load balancing for development branches
                  sh("sed -i.bak 's#LoadBalancer#ClusterIP#' ./k8s/services/service.yaml")
                  sh("sed -i.bak 's#docker.adzkia.web.id/ramadoni/nginx-hello:latest#${imageTag}#' ./k8s/dev/*.yaml")
                  sh("kubectl --namespace=${env.BRANCH_NAME} apply -f k8s/services/")
                  sh("kubectl --namespace=${env.BRANCH_NAME} apply -f k8s/dev/")
                  echo 'To access your environment run `kubectl proxy`'
                  echo "Then access your service via http://localhost:8001/api/v1/proxy/namespaces/${env.BRANCH_NAME}/services/${appName}:80/"
                }
          }     
        }
     }
}
