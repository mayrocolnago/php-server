Para visualizar a versão deste README em Português (Brasil), [clique aqui](README-pt_BR.md)

# PHP Development/Production server

Development/Production environment with Apache2 + PHP 7.4 + MySQL 8 + PhpMyAdmin 5.0.4 + Wildcard vHosts

Index
-

  - [How to use](#how-to-use)

  - [Requirements](#requirements)

  - [Functionalities](#functionalities)

  - [Installation](#installation-linuxunix)

    - [Linux/Unix platform](#installation-linuxunix)

    - [Mac platform](#installation-mac)

    - [Windows platform](#installation-windows)

  - [About the container](#about-the-container)

  - [Hosting projects](#hosting-projects)

  - [Reverse proxy](#reverse-proxy)

  - [Firewall](#firewall)

  - [SSL/HTTPs Certs](#sslhttps-certs)


# How to use

Paste/Clone your *PROJECT* inside **"html"**. Container will make it available by accessing *PROJECT*.domain whenever hosts points towards your machine


# Requirements

All you need to do is have **git** and **docker** installed with **docker-compose**, then run `docker-compose up -d`

And you are good to go.


# Functionalities

  - include(**AUTH_MODULE**);

  - include(**DOMPDF_MODULE**);

  - include(**PDOMYSQL_MODULE**);

  - include(**PHPMAIL_MODULE**);

  - include(**SSH2SECLIB_MODULE**);

  - include(**SFTPSECLIB_MODULE**);

  - include(**SCPSECLIB_MODULE**);

  - Global accessible variables on **environment.php**


# Installation Linux/Unix

1. Install docker

```
sudo apt-get -y update &&
sudo apt-get -y upgrade &&
sudo apt-get install -y apt-transport-https ca-certificates curl git gnupg-agent software-properties-common && 
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add - &&
sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" &&
sudo apt-get install -y docker-ce docker-ce-cli containerd.io &&
sudo curl -L "https://github.com/docker/compose/releases/download/1.27.4/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose && sudo chmod +x /usr/local/bin/docker-compose
```

2. Clone this repository

```
sudo git clone https://github.com/mayrocolnago/php-server /var/www && 
sudo chmod -R 777 /var/www
```

3. Start your container

```
docker-compose up -d
```

That's it.


# Installation Mac

1. Install docker

https://www.docker.com/products/docker-desktop


2. Clone this repository

```
sudo git clone https://github.com/mayrocolnago/php-server /var/www && 
sudo chmod -R 777 /var/www
```

3. Start your container

```
docker-compose up -d
```

That's it.


# Installation Windows

1. Install WSL2 Linux Core

https://wslstorestorage.blob.core.windows.net/wslblob/wsl_update_x64.msi


2. Install docker

https://www.docker.com/products/docker-desktop


3. Clone this repository

```
git clone https://github.com/mayrocolnago/php-server "$USERPROFILE\Server"
```

4. Start your container

```
docker-compose up -d
```

That's it.


# About the container

To shutdown the container, just type on the project folder

```
docker-compose down
```


To restart your container services, also head to the project folder and type

```
docker-compose restart
```


To get updates of this repository, just type

```
git commit -a -m "Just update" && git pull
```


To get inside the container once it's up, use the following command

```
docker exec -it $(docker ps -f "name=phpws" -n 1 --format "{{.ID}}") /bin/bash
```


# Hosting projects

The **/html** folder comes ready with the wildcard vhost configuration from **bin/sites/wildcard.conf** to receive projects folders.

All you need is clone or start any project at **html/YOURPROJECT**

It will become available through **YOURPROJECT.any.domain.com**

> Anything relaying from behalf of the first dot will take root place at the respective folder on /html


# Reverse proxy

By default, **Bind9 configuration** container comes disabled due to most platforms port usage.

If you want to make wildcard **anything.localhost** redirects to yourself, uncomment *bind* lines on **docker-compose.yml**


# Firewall

There is a fully automated *firewall* script on **bin/firewall** which you should really considering use as soon as you finish up configuring your container on the host machine


# SSL/HTTPs Certs

To set a specific virtual host, please head to **bin/sites/**

In order to generate a new certificate to a specific vhost you must enter the following command on the host machine

```
docker exec -it $(docker ps -f "name=phpws" -n 1 --format "{{.ID}}") /bin/bash -c certbot\ --apache\ -d\ EXAMPLE.COM
```

It will get the webcontainer ID and send the certbot trigger to certify the *EXAMPLE.COM* domain on apache.

> ps. Please use lowercase on domain names (the uppercase above is illustrative)
