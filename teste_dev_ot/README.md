📁 Desafio API de Upload e Busca de Arquivos (OT)

Este projeto implementa uma API REST para upload robusto de arquivos CSV, com controle de duplicidade via hash, armazenamento dos dados importados e busca paginada no conteúdo dos arquivos.

Todo o ambiente de desenvolvimento é conteinerizado com Docker Compose, garantindo padronização e facilidade de setup.

🚀 Stack Utilizada

Linguagem: PHP ^8.2

Framework: Laravel ^12.0

Banco de Dados: MySQL

Containerização: Docker & Docker Compose

Debug: laravel/tinker ^2.10.1

Documentação: OpenAPI (Swagger)

📦 Funcionalidades

Upload de arquivos CSV via API

Controle de duplicidade utilizando hash do arquivo

Importação do conteúdo do CSV para o banco de dados

Histórico de arquivos enviados

Busca paginada nos dados importados

Documentação interativa com Swagger UI

⚙️ Instalação e Configuração
Pré-requisitos

Docker (Docker Engine + Docker CLI)

Docker Compose (normalmente incluído no Docker Desktop)

🔧 Configurar o Ambiente

Crie o arquivo .env a partir do .env.example e ajuste as variáveis abaixo:

DB_CONNECTION=mysql
DB_HOST=desafio-db-1
DB_PORT=3306
DB_DATABASE=desafio-ot
DB_USERNAME=root
DB_PASSWORD=senhadobancoaqui

🐳 Build e Inicialização dos Containers

Execute o comando abaixo para construir as imagens e iniciar os serviços (Laravel, Nginx e MySQL):

docker-compose up --build -d

🛠️ Configuração do Laravel

Após os containers estarem ativos, execute os comandos dentro do container da aplicação:

Instalar dependências PHP
docker exec -it desafio-app-1 composer install

Gerar a chave da aplicação
docker exec -it desafio-app-1 php artisan key:generate

Executar as migrações
docker exec -it desafio-app-1 php artisan migrate

🧪 Comandos Essenciais para Debug
Visualizar logs do Laravel
docker exec -it desafio-app-1 tail -f storage/logs/laravel.log

Acessar o MySQL
docker exec -it desafio-db-1 mysql -u root -p

Limpar dados para novos testes

Dentro do MySQL:

USE `desafio-ot`;

TRUNCATE TABLE uploaded_files;
TRUNCATE TABLE file_contents;

Acessar o bash do container da aplicação
docker exec -it desafio-app-1 bash

📚 Documentação da API (Swagger)

A API possui documentação interativa utilizando Swagger UI.

URL de acesso:

GET http://localhost:8080/api/documentation

🔌 Endpoints da API
Upload de arquivo CSV

Realiza o upload e importa o conteúdo do arquivo.

POST /api/upload


Espera multipart/form-data

Campo obrigatório: file

Histórico de uploads

Lista todos os arquivos enviados.

GET /api/history

Busca no conteúdo dos arquivos

Busca paginada (20 registros por página por padrão) nos dados importados.

GET /api/file-content
