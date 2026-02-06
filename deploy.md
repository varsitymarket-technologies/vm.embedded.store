# Deploying Using Docker 

## Setting Permissions 
- SQLite needs to write to the directory to create the database and temporary "journal" files
- On Linux, you might need to give the Docker container permission to write to your folder

```shell
sudo chown -R 33:33 .
```



