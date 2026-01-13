# 📁 Desafio API de Upload e Busca de Arquivos (OT)

> API REST para upload robusto de arquivos CSV, controle de duplicidade via hash e busca paginada no conteúdo importado.  
> Ambiente totalmente conteinerizado com Docker Compose.

---

## 🚀 Stack Utilizada

- 🐘 **PHP** ^8.2  
- ⚡ **Laravel** ^12.0  
- 🛢 **MySQL**  
- 🐳 **Docker & Docker Compose**  
- 🧪 **Laravel Tinker**  

---

## ✨ Funcionalidades

- 📤 Upload de arquivos CSV / Excel
- 🔐 Prevenção de duplicidade via hash
- 📥 Importação dos dados para o banco
- 🕓 Histórico de arquivos enviados
- 🔎 Busca paginada nos dados importados
---

## ⚙️ Instalação

### 📌 Pré-requisitos

- Docker (Engine + CLI)
- Docker Compose

---

## 🔧 Configuração do Ambiente

Crie o arquivo `.env` baseado no `.env.example`:

```env
DB_CONNECTION=mysql
DB_HOST=desafio-db-1
DB_PORT=3306
DB_DATABASE=desafio-ot
DB_USERNAME=root
DB_PASSWORD=senhadobancoaqui
```

### 🐳 Build e Inicialização dos Containers
```bash
DB_CONNECTION=mysql
DB_HOST=desafio-db-1
DB_PORT=3306
DB_DATABASE=desafio-ot
DB_USERNAME=root
DB_PASSWORD=senhadobancoaqui
```

📦 Instalar dependências
```bash
docker exec -it desafio-app-1 composer install
```
🔑 Gerar chave da aplicação
```bash
docker exec -it desafio-app-1 php artisan key:generate
```
🗄 Executar migrações
```bash
docker exec -it desafio-app-1 php artisan migrate
```
## 🧪 Debug & Utilidades
📄 Logs do Laravel
```bash
docker exec -it desafio-app-1 tail -f storage/logs/laravel.log
```
🛢 Acessar MySQL
```bash
docker exec -it desafio-db-1 mysql -u root -p
```
♻️ Limpar dados para novos testes
```sql
USE `desafio-ot`;

TRUNCATE TABLE uploaded_files;
TRUNCATE TABLE file_contents;
```
## 🔌 Endpoints
📤 Upload de Arquivo CSV
```http
POST /api/upload
multipart/form-data
```

🕓 Histórico de Uploads
```http
GET /api/file-search
```
🔎 Busca no Conteúdo Importado
```http
GET /api/content-search
```

## 📌 Observações Finais
Testes realizados via Insomnia

Estrutura preparada para melhorias de performance

Projeto desenvolvido com foco em robustez e escalabilidade

# 🧑‍💻 Autor
Desenvolvido por Pedro Lopes

Desafio técnico – OT
