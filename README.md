# extract_products_to_xml
Application purpose is to extract product lists from SQL database to XML file.

## Installation
You need to set up MySQL server configuration in `dbconfig.php` file
```dosini
$host="127.0.0.1";
$username="<your-db-username>";
$password="<db-password>";
$dbname="<db-name-for-products>";
```

## Usage
Just run as regular PHP script
```bash
php extract_products_to_xml.php
```
