# 🚀 Task Management API

This project is a robust Task Management System built to showcase proficiency in modern PHP/Laravel backend engineering. It serves as a comprehensive portfolio piece, demonstrating high standards in clean code architecture, automated testing, and professional deployment using containerized environments.

## 🛠 Tech Stack & Environment
- **Framework:** Laravel 12.
- **Infrastructure:** Dockerized via **Laravel Sail**.
- **Operating System:** Optimized for **WSL 2 (Ubuntu)**.
- **Database:** MySQL.
- **Mailing:** Mailpit (for local notification testing).

## ✅ Quality Assurance (Testing)
The core logic is fully protected by automated tests. I have implemented **11 Feature and Unit tests** ensuring 100% success rate for:
- User Authentication (Sanctum).
- Task CRUD operations.
- Commenting system & Email notifications.
- File attachment validation.

**Current Test Status:** `11 passed (100%)`.

## 📦 Getting Started

To run this project locally, follow these steps in your **WSL/Ubuntu terminal**:

1. **Clone the repository and enter the directory.**

2. **Setup environment:**
   ```bash
   cp .env.example .env

3. **Start Docker environment:**
   ```bash
   ./vendor/bin/sail up -d

4. **Run Database Migrations:**
   ```bash
   ./vendor/bin/sail artisan migrate

5. **Generate API Documentation:**
   ```bash
   ./vendor/bin/sail artisan l5-swagger:generate

## 📖 API Documentation & Monitoring
* **Swagger UI:** Accessible at [http://localhost/api/documentation](http://localhost/api/documentation) to explore and test endpoints interactively.
* **Mailpit:** Monitor sent emails at [http://localhost:8025](http://localhost:8025).

---
*Developed with focus on scalability and clean code.*