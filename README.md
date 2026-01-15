# CI4 Comércio - Ambiente Docker

## Estrutura de Pastas

```
C:\laragon\www\ci4comercio\
├── docker-compose.yml
├── docker/
│   ├── php/
│   │   └── Dockerfile
│   └── nginx/
│       └── default.conf
└── src/
    ├── app/
    ├── public/
    ├── writable/
    └── ... (código CodeIgniter 4)
```

## Pré-requisitos

- Docker Desktop instalado
- Docker Compose instalado
- Código fonte do CodeIgniter 4 na pasta `src/`

## Configuração Inicial

### 1. Verificar estrutura do CodeIgniter 4

Certifique-se de que dentro da pasta `src/` você tem a estrutura padrão do CI4:
- `app/` - Código da aplicação
- `public/` - Diretório público (index.php)
- `writable/` - Diretório de escrita (logs, cache, uploads)
- `vendor/` - Dependências do Composer
- `.env` - Arquivo de configuração

### 2. Configurar o arquivo .env do CodeIgniter 4

No arquivo `src/.env`, configure as variáveis de banco de dados:

```env
# ENVIRONMENT
CI_ENVIRONMENT = development

# DATABASE
database.default.hostname = mysql
database.default.database = ci4comercio_db
database.default.username = ci4comercio_user
database.default.password = ci4comercio_P@ssw0rd_2024
database.default.DBDriver = MySQLi
database.default.DBPrefix = 
database.default.port = 3306

# REDIS CACHE
cache.redis.host = redis
cache.redis.password = 
cache.redis.port = 6379
cache.redis.timeout = 0
cache.redis.database = 0

# APP
app.baseURL = http://localhost:56200/
app.indexPage = ''
app.forceGlobalSecureRequests = false
```

### 3. Ajustar permissões da pasta writable

```bash
# No Windows (PowerShell como Administrador)
icacls "C:\laragon\www\ci4comercio\src\writable" /grant Everyone:F /T

# Ou no terminal do container depois de iniciar:
docker exec -it ci4comercio_php chmod -R 777 /var/www/html/writable
```

## Comandos Docker

### Iniciar os containers

```bash
# Na pasta do projeto (C:\laragon\www\ci4comercio\)
docker-compose up -d
```

### Parar os containers

```bash
docker-compose down
```

### Ver logs dos containers

```bash
# Todos os containers
docker-compose logs -f

# Container específico
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f mysql
```

### Acessar o container PHP

```bash
docker exec -it ci4comercio_php sh
```

### Instalar dependências do Composer

```bash
# Executar dentro do container PHP ou:
docker exec -it ci4comercio_php composer install
docker exec -it ci4comercio_php composer update
```

### Executar migrations do CodeIgniter 4

```bash
docker exec -it ci4comercio_php php spark migrate
```

### Limpar cache do CodeIgniter 4

```bash
docker exec -it ci4comercio_php php spark cache:clear
```

## Acessar os Serviços

| Serviço | URL | Descrição |
|---------|-----|-----------|
| **Aplicação** | http://localhost:56200 | CodeIgniter 4 Application |
| **Adminer** | http://localhost:56202 | Interface de gerenciamento MySQL |
| **MySQL** | localhost:56201 | Banco de dados MySQL |
| **Redis** | localhost:56203 | Cache Redis |

### Credenciais do MySQL

**Via Adminer (http://localhost:56202):**
- **Sistema:** MySQL
- **Servidor:** mysql
- **Usuário:** ci4comercio_user
- **Senha:** ci4comercio_P@ssw0rd_2024
- **Base de dados:** ci4comercio_db

**Root Access:**
- **Usuário:** root
- **Senha:** root_S3cur3P@ss_2024

## Solução de Problemas

### Erro: "Permission denied" no writable

```bash
docker exec -it ci4comercio_php chmod -R 777 /var/www/html/writable
```

### Erro: "Database connection failed"

1. Aguarde o MySQL inicializar completamente (pode levar 30-60 segundos)
2. Verifique se o arquivo `.env` está correto
3. Teste a conexão:
```bash
docker exec -it ci4comercio_mysql mysql -u ci4comercio_user -p
# Digite: ci4comercio_P@ssw0rd_2024
```

### Erro 502 Bad Gateway no Nginx

1. Verifique se o container PHP está rodando:
```bash
docker ps | grep ci4comercio_php
```

2. Reinicie os containers:
```bash
docker-compose restart
```

### Recriar os containers do zero

```bash
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

## Recursos do PHP

O container PHP inclui as seguintes extensões:
- ✅ intl (internacionalização)
- ✅ mbstring (multibyte string)
- ✅ mysqli / pdo_mysql
- ✅ gd (manipulação de imagens)
- ✅ zip
- ✅ redis
- ✅ opcache (cache de código)
- ✅ curl
- ✅ json
- ✅ xml

## Configurações do PHP

- **Memória:** 512M
- **Upload máximo:** 50M
- **Tempo de execução:** 300s
- **Timezone:** America/Sao_Paulo

## Estrutura de URLs do CodeIgniter 4

Com a configuração atual:
- ✅ URLs limpas (sem index.php)
- ✅ Rotas personalizadas funcionando
- ✅ Rewrite automático pelo Nginx

**Exemplos:**
- http://localhost:56200/
- http://localhost:56200/produtos
- http://localhost:56200/api/usuarios

## Comandos Úteis do CodeIgniter 4

```bash
# Listar todas as rotas
docker exec -it ci4comercio_php php spark routes

# Criar um controller
docker exec -it ci4comercio_php php spark make:controller NomeController

# Criar um model
docker exec -it ci4comercio_php php spark make:model NomeModel

# Criar uma migration
docker exec -it ci4comercio_php php spark make:migration NomeMigration

# Executar migrations
docker exec -it ci4comercio_php php spark migrate

# Reverter migration
docker exec -it ci4comercio_php php spark migrate:rollback

# Criar seeder
docker exec -it ci4comercio_php php spark make:seeder NomeSeeder

# Executar seeder
docker exec -it ci4comercio_php php spark db:seed NomeSeeder
```

## Backup do Banco de Dados

```bash
# Criar backup
docker exec ci4comercio_mysql mysqldump -u root -proot_S3cur3P@ss_2024 ci4comercio_db > backup.sql

# Restaurar backup
docker exec -i ci4comercio_mysql mysql -u root -proot_S3cur3P@ss_2024 ci4comercio_db < backup.sql
```

## Performance

O ambiente está otimizado com:
- OPcache ativado para cache de código PHP
- Redis para cache de aplicação
- Nginx com cache de arquivos estáticos
- MySQL 8.0 com healthcheck

## Suporte

Para problemas específicos do CodeIgniter 4:
- https://codeigniter.com/user_guide/
- https://forum.codeigniter.com/

Para problemas com Docker:
- https://docs.docker.com/