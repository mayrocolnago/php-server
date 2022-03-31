# Servidor de desenvolvimento/produção em PHP

Ambiente de desenvolvimento e produção com Apache2 + PHP 7.4 + MySQL 8 + PhpMyAdmin 5.0.4 + Virtual hosts coringas

Índice
-

  - [Como usar](#como-usar)

  - [Requerimentos](#requerimentos)

  - [Funcionalidades](#funcionalidades)

  - [Instalação](#instalacao)

    - [Plataforma Linux/Unix](#instalação-linuxunix)

    - [Plataforma Mac](#instalação-mac)

    - [Plataforma Windows](#instalação-windows)

  - [Sobre o container](#sobre-o-container)

  - [Hospedando projetos](#hospedando-projetos)

  - [Proxy reverso](#proxy-reverso)

  - [Firewall](#firewall)

  - [Certificados SSL/HTTPs](#certificados-sslhttps)


# Como usar

Cole ou clone seu *projeto* dentro da pasta **html**. Os serviços do continer irão automaticamente tornar acessível através do endereço *projeto*.localhost na sua própria máquina.


# Requerimentos

Tudo o que você precisa é ter o **git** e o **docker** instalado junto com o **docker-compose**, e então executar `docker-compose up -d`

E o servidor estará pronto.


# Funcionalidades

  - include(**AUTH_MODULE**);

  - include(**DOMPDF_MODULE**);

  - include(**PDOMYSQL_MODULE**);

  - include(**PHPMAIL_MODULE**);

  - include(**SSH2SECLIB_MODULE**);

  - include(**SFTPSECLIB_MODULE**);

  - include(**SCPSECLIB_MODULE**);

  - Variáveis globais dos módulos acessíveis devido ao **environment.php** (o qual é chamado automaticamente em cada acesso)


# Instalação em Linux/Unix

1. Instale o docker

```
sudo apt-get -y update &&
sudo apt-get -y upgrade &&
sudo apt-get install -y apt-transport-https ca-certificates curl git gnupg-agent software-properties-common && 
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add - &&
sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" &&
sudo apt-get install -y docker-ce docker-ce-cli containerd.io &&
sudo curl -L "https://github.com/docker/compose/releases/download/1.27.4/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose && sudo chmod +x /usr/local/bin/docker-compose
```

2. Clonar este repositório

```
sudo git clone https://github.com/mayrocolnago/php-server /var/www && 
sudo chmod -R 777 /var/www
```

3. Iniciar seu container

```
docker-compose up -d
```

Pronto.

> Testado com Ubuntu 18.04


# Instalação em Mac

1. Instale o docker

https://www.docker.com/products/docker-desktop


2. Clonar este repositório

```
sudo git clone https://github.com/mayrocolnago/php-server /var/www && 
sudo chmod -R 777 /var/www
```

3. Iniciar seu container

```
docker-compose up -d
```

Pronto.

> Testado com Mac 10+


# Instalação em Windows

1. Install WSL2 Linux Core

https://wslstorestorage.blob.core.windows.net/wslblob/wsl_update_x64.msi


2. Instale o docker

https://www.docker.com/products/docker-desktop


3. Clonar este repositório

```
git clone https://github.com/mayrocolnago/php-server "$USERPROFILE\Server"
```

4. Iniciar seu container

```
docker-compose up -d
```

Pronto.

> Testado em Windows 7/8/10 (No windows pode ser necessário algumas extensões extras)


# Sobre o container

Para desligar o ambiente em sua máquina, entre na pasta do projeto via terminal e digite:

```
docker-compose down
```


Para reiniciar o serviço, entre na pasta do projeto e digite:

```
docker-compose restart
```


Para receber updates deste repositório sem perder suas alterações, digite:

```
git commit -a -m "Just update" && git pull
```


Para acessar o container uma vez que este já está em pé, digite:

```
docker-compose exec www bash
```

> Nome dos serviços para acesso no shell do container são: www, mysql, bind

*obs: Substitua o que estiver entre chaves por qual dos serviços deseja acessar. Exemplo: docker-compose exec www bash*


# Hospedando projetos

A pasta **/html** vem pronta com os virtual hosts configurados em **bin/sites/wildcard.conf** para já acessar o que estiver dentro dela.

Tudo o que você precisa é clonar a pasta de seu projeto para dentro de **html/SEUPROJETO**

E ele estará disponível através do link **SEUPROJETO.seu.dominio**

> Qualquer coisa que estiver antes do primeiro ponto no nome do domínio irá acessar o nome da pasta em /html


# Proxy reverso

Por padrão, a configuração do **Bind9** vem desativada pois a maioria dos sistemas operacionais já usam a porta 53.

Se você quer que **qualquercoisa.localhost** redirecione para você próprio pra que possa usar as pastas como sites, habilite as linhas comentadas do serviço **bind** no arquivo **docker-compose.yml**


# SSL/HTTPs Certs

Para especificar um certificado HTTPS para um site, por favor, insira-o em **bin/sites/**

Para gerar um certificado digital para um host especifico, entre com o comando:

```
docker-compose exec www bash -c certbot\ --apache\ -d\ EXAMPLE.COM
```

Este comando irá pegar o ID do container web e enviar o comando para o certbot gerar o certificado para *EXAMPLE.COM* no apache.

> obs. Por favor, use apenas caracteres minusculos em nomes de domínio (os exemplos com maísculo são meramente ilustrativos)
