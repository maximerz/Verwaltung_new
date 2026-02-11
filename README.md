# PHP Customer Management System

This is a simple PHP web application for managing customers and orders.

## Setup

1. The application will automatically try to connect to MySQL first
2. If MySQL fails, it falls back to SQLite
3. Database tables are created automatically

## Files

- `db_connection.php` - Database connection with MySQL/SQLite fallback
- `web_oberflaeche.php` - Main web interface
- `create_customer.php` - Create new customer
- `create_order.php` - Create new order
- `test_db.php` - Test database connection
- `setup_mysql.sql` - MySQL database schema
- `setup_complete.sh` - Automated MySQL setup script

## Usage

1. Open `web_oberflaeche.php` in your web browser
2. View existing companies, customers, and orders
3. Use the forms to create new customers and orders

## Database Schema

### firma (Companies)
- id (Primary Key)
- firmenname (Company Name)
- strasse (Street)
- ort (City)

### kundensystem (Customers)
- id (Primary Key)
- Vorname (First Name)
- Nachname (Last Name)
- EMail (Email)

### bestellungen (Orders)
- idbestellung (Primary Key)
- Kundennummer (Customer Number)
- Bestellugsnummer (Order Number)
- Bestellunsname (Order Name)
- Angebotsnummer (Quote Number)
- Auftragsnummer (Order Number)
- Auslieferung (Delivery)
- Lieferschein (Delivery Note)
- Lieferzeit (Delivery Time)

## Troubleshooting

- If MySQL connection fails, the app automatically uses SQLite
- Check `test_db.php` to verify database connection
- Xdebug connection errors are normal for command-line execution