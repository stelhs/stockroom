Stockroom
=========


Description
-----------

Stockroom is an open source php server aimed at robust cataloging of large
numbers of items. Its main features are:

- Double tree hierarchy (category + location)
- Items attributes
- Tracking of storage capacity
- Aiming at smartphone usage


How to install
--------------

The following instruction is for Ubunu. Similar instructions will be user
in other distributions.

- Stockroom is designed to run on LAMP. To install it, run:
```sh
sudo apt install -y apache2 mysql-server php git
```

- Stockroom also depends on the php_common repository by Michail Kurochkin. It
has to be cloned as **/usr/lib/php**:

```sh
sudo mkdir /usr/lib/php
sudo chmod 777 /usr/lib/php
git clone https://github.com/stelhs/php_common.git /usr/lib/php
```

- Clone the Stockroom repository to the desired directory (root from now on).
The standart apache2 path is **/var/www/html**, which will be used in this guide:

```sh
root="/var/www/html/stockroom"

sudo mkdir -p ${root}
sudo chmod 777 ${root}
git clone https://github.com/stelhs/stockroom.git ${root}
```

- The following statements stand for providing a place for storing images:

```sh
mkdir ${root}/i/obj
chgrp www-data ${root}/i/obj
chmod g+rwx ${root}/i/obj
```

*www-data* is the standart group of apache2 process. In case of customized one
it shoud be changed properly in *chgrp* command arguments.

- MySQL database has to be configured in order to use the Stockroom:

```sh
sudo mysql -e " \
    create database stockroom; \
    CREATE USER 'stockroom'@'localhost' IDENTIFIED BY 'stockroom'; \
    GRANT ALL PRIVILEGES ON stockroom.* TO 'stockroom'@'localhost'; \
    "
sudo mysql stockroom < ${root}/private/db_struct.sql

# The following lines with substitution of %desired login% and %desired password%
# have to be performed once MySQL cli is entered and then Ctrl+D pressed:
#
# use stockroom;
# INSERT INTO `users` (`id`, `login`, `pass`, `hash`, `role`, `created`) \
# VALUES (NULL, '%desired login%', password('%desired password%'), \
# sha2(password('%desired password%'), 256), 'admin', CURRENT_TIMESTAMP);
sudo mysql
"
```

***N.B.!*** In case of an error, the *password* function in the SQL query above has to
be replaced on *md5*. Same has to be done with the **6**th line of
**${root}/private/user.php**.

Names and password can be arbitrary, but they have to be set correspondingly
later.

- Two files have to be added to the **private** directory:

The contents of the **${root}/private/.database.json**:
```text
{
    "host": "localhost",
    "user": "stockroom",
    "pass": "stockroom",
    "database": "stockroom",
    "port" : "3306"
}

```

In case of using different input in previous point or running *mysqld* on
non-standart port, the field have to be set correspondingly.

The contents of the **${root}/private/.path.json**:
```text
{
    "http_root_path": "/stockroom/",
    "absolute_root_path": "/var/www/html/stockroom/"
}
```

If other root is in use, the fields have to be set correspondingly.

- In order to load high quality pictures of items, the upload limit for
apache2's php has to be changed in **php.ini**.

```sh
# Works in case there's only one version of php is installed. Otherwise, the
# exact version of PHP has to be written instead of '*':
sudo nano /etc/php/*/apache2/php.ini
```

The **upload_max_filesize** property has to be changed, for instance, on 500M.

After the change apache2 has to be restarted to apply it:
```sh
sudo systemctl restart apache2
```