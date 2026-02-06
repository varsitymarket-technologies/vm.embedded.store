# Deploying Application
- The Recomended installation for this application. 

## 1. Installing Docker 
- For production enviromanet we will recommend operating docker
```shell 
# Installing Docker
sudo apt install docker.io

# Installing Docker Compose 
sudo apt install docker-compose
```

## 2. Installing NGINX 
- We will use nginx as the web host app. 
```shell 
# Installing Nginx 
sudo apt install nginx 
```

## 3. Installing Certbot 
- We will also use certbot for the ssl certificates and auto-renewal of these certificates. 
```shell 
# Installing Certbot 
sudo apt install certbot
```

## 4. Setting Up Application 
- Clone the repo from the github source 
- Start The Docker Application.

```shell 
#Clone Github Repo 
git clone https://github.com/varsitymarket-technologies/vm.embedded.store.git vm.sites

# Changing Directory 
cd vm.sites 

# Composing Docker File 
docker-compose up -d 
```

## 5. Verify Runing Application 
- This script will show all the applications that docker is runing. 
```shell 
docker ps -a 
```

## 6. Setting Permissions 
- SQLite needs to write to the directory to create the database and temporary "journal" files
- On Linux, you might need to give the Docker container permission to write to your folder
- You can keep ownership but change the group of the folder to www-data (or GID 33) and give the group write access.
On the host: 
```shell
sudo chgrp -R 33 .
chmod -R 775 .
# This gives you rwx (Read/Write/Execute) and gives the container group rwx.
```

## 7. Connecting Your Domain 
- By default the application will run on port 7700. For the purpose of this guide we will also use port 7700. 
- The Domain we will use to test this will be the following `sites.example.com`. 

- Make sure `sites.example.com` is pointed to the websers ip address/cname records. 

* Creating Your Domain Configuration
```shell
# sudo nano /etc/nginx/sites-available/[domain]
sudo nano /etc/nginx/sites-available/sites.example.com.conf
```

* Nginx Configuration 
```conf 
# Define the tunnel and the fallback
upstream tunnel_backend {
    server 127.0.0.1:7700 max_fails=1 fail_timeout=10s;
    # This 'backup' kicks in if the tunnel port is closed
    server 127.0.0.1:7701 backup; 
}

server {
    listen 80;
    server_name sites.example.com;

    location / {
        proxy_pass http://tunnel_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        
        # Error handling to force the switch to backup quickly
        proxy_next_upstream error timeout invalid_header http_502 http_503 http_504;
    }
}

# 3. The Internal "Offline Page" Server Block
# This listens on a private port only accessible by the server itself
server {
    listen 127.0.0.1:7071;
    server_name localhost;

    location / {
        root /var/www/html/offline;
        index index.html;
    }
}
```

* Linking Domain Configuration 
```shell 
sudo ln /etc/nginx/sites-available/sites.exampel.com.conf /etc/nginx/sites-enabled/
```

* Testing Configuration
- Once Nginx confirms that your configuration is correct you can proceed.
```shell 
nginx -t 
```

* Restart Nginx
```shell 
systemctl restart nginx; 
```


- `sites.example.com` should be live.  


