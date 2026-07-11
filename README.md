# Uni.Book — University Resource Booking System

Uni.Book is a web-based system that lets universities manage the booking of shared resources — facilities, vehicles, personnel, and equipment — through a simple online portal instead of manual, paper-based requests.

## Objectives

1. Provide a self-service portal for users, managers, and admins to browse and book university resources online.
2. Implement role-based authentication with separate access levels for admin, resource manager, and user.
3. Provide full CRUD operations for resources and bookings, with conflict detection to prevent double bookings.
4. Provide a booking approval workflow and online payment system with receipt generation.
5. Provide reporting and dashboard analytics for admins and managers to monitor bookings, resources, and revenue.

## User Roles

| Role | Access |
|---|---|
| **Admin** | Full access — manage all users, resources, bookings, payments/refunds, and system reports. |
| **Resource Manager** | Manages resources and bookings within their assigned category/department (Facilities, Vehicles, Equipment, or Personnel). |
| **User** | Browses resources, submits bookings, makes payments, and tracks booking status. |

## Core Features

- Role-based login for Admin, Resource Manager, and User
- Resource catalogue with category filtering and search
- Booking creation, editing, cancellation, and conflict detection
- Booking approval workflow (Pending → Approved/Rejected → Completed)
- Online payment via ToyyibPay sandbox, with auto-generated receipts
- Refund handling for cancelled paid bookings
- Admin/Manager dashboards with charts and booking statistics
- Booking calendar with monthly view and category filters
- PDF report export (financial & booking reports)
- JSON file fallback if MySQL is unavailable

## Tech Stack

- **Backend:** PHP 8.1+
- **Database:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3 (custom responsive design), vanilla JavaScript
- **Payment Gateway:** ToyyibPay (sandbox)
- **Local Server:** XAMPP / WAMP / LAMP

## Setup

1. Install XAMPP and start Apache + MySQL.
2. Copy the project folder into `C:\xampp\htdocs\unibook - Copy`.
3. Go to `http://localhost/phpmyadmin`, create a database (`unibook_db`), and import `schema.sql`.
4. Check `db_config.php` matches your local DB settings.
5. Open `http://localhost/unibook%20-%20Copy/index.php` in Chrome to access the app.

## Default Login Credentials

| Role | Email / Username | Password |
|---|---|---|
| Admin | admin | pass1234 |
| User | siti@gmail.com | 123456 |
| Resource Manager (Facilities) | facilitiesmanager@unibook.com | Manager123! |
| Resource Manager (Transport) | transportmanager@unibook.com | Manager123! |
| Resource Manager (ICT) | ictmanager@unibook.com | Manager123! |
| Resource Manager (HR) | hrmanager@unibook.com | Manager123! |

## Team

- Nur Aliah Nadhirah binti Alias — Frontend Developer, QA & Documentation
- Nursyaza Amira binti Ahmad Ghazali — Database Administrator
- Salsabila binti Hasrulnizam — Project Manager, Backend Developer

## Contact

- Support Email: unibook@gmail.com
