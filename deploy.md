# Deploying Using Docker 

## Setting Permissions 
- SQLite needs to write to the directory to create the database and temporary "journal" files
- On Linux, you might need to give the Docker container permission to write to your folder

You can keep ownership but change the group of the folder to www-data (or GID 33) and give the group write access.


On the host: 
```shell
sudo chgrp -R 33 .

chmod -R 775 .

# This gives you rwx (Read/Write/Execute) and gives the container group rwx.
```



