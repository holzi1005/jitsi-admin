#!/usr/bin/env bash
echo --------------Schutdown Apache------------------------------------------
#service apache2 stop

PATHName=/var/www/html
echo --------------------------------------------------------------------------
echo ----------------Create Database-------------------------------------------
echo ----------------Please Backup your database-------------------------------
echo --------------------------------------------------------------------------
php $PATHName/bin/console cache:clear
php $PATHName/bin/console doctrine:mig:mig --no-interaction
#php bin/console doctrine:migrations:migrate --no-interaction
echo --------------------------------------------------------------------------
echo -----------------Clear Cache----------------------------------------------
echo --------------------------------------------------------------------------
php $PATHName/bin/console cache:clear
php $PATHName/bin/console cache:warmup
echo --------------------------------------------------------------------------
echo ----------------Setting Permissin-----------------------------------------
echo --------------------------------------------------------------------------
chown -R www-data:www-data $PATHName/var/cache
chmod -R 775 $PATHName/var/cache
chown -R www-data:www-data $PATHName/public/uploads
chmod -R 775 $PATHName/public/uploads
echo --------------------------------------------------------------------------
echo -----------------------Updated the Jitsi-Admin correct------------------
echo --------------------------------------------------------------------------



