echo Welcome to the installer:
FILE=docker.conf
if test -f "$FILE"; then
  source $FILE
else
  NEW_UUID=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  HTTP_METHOD=$1
  PUBLIC_URL=$2
  KEYCLOAK_PW=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  JITSI_ADMIN_PW=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  MERCURE_JWT_SECRET=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  echo "NEW_UUID=$NEW_UUID" >> $FILE
  echo "HTTP_METHOD=$HTTP_METHOD" >> $FILE
  echo "PUBLIC_URL=$PUBLIC_URL" >> $FILE
  echo "JITSI_ADMIN_PW=$JITSI_ADMIN_PW" >> $FILE
  echo "KEYCLOAK_PW=$KEYCLOAK_PW" >> $FILE
  echo "MERCURE_JWT_SECRET=$MERCURE_JWT_SECRET" >> $FILE


  echo --------------------------------------------------------------------------
  echo -----------------We looking for all the other parameters-------------------
  echo --------------------------------------------------------------------------
  echo -------------------------------------------------------------
  echo -----------------Mailer--------------------------------------
  echo -------------------------------------------------------------
  read -p "Enter smtp host: " smtpHost
  read -p "Enter smtp port: " smtpPort
  read -p "Enter smtp username: " smtpUsername
  read -p "Enter smtp password: " smtpPassword
  read -p "Enter SMTP encrytion tls/ssl/none: " smtpEncryption
  echo "smtpHost=$smtpHost" >> $FILE
  echo "smtpPort=$smtpPort" >> $FILE
  echo "smtpUsername=$smtpUsername" >> $FILE
  echo "smtpPassword=$smtpPassword" >> $FILE
  echo "smtpEncryption=$smtpEncryption" >> $FILE
fi

sed -i "s|<clientsecret>|$NEW_UUID|g" keycloak/realm-export.json
sed -i "s|<clientUrl>|$HTTP_METHOD://$PUBLIC_URL|g" keycloak/realm-export.json
sed -i "s|<jitsi-admin-pw>|$JITSI_ADMIN_PW|g" docker-entrypoint-initdb.d/init-userdb.sql
sed -i "s|<keycloak-pw>|$KEYCLOAK_PW|g" docker-entrypoint-initdb.d/init-userdb.sql

export MAILER_HOST=$smtpHost
export MAILER_PORT=$smtpPort
export MAILER_PASSWORD=$smtpPassword
export MAILER_USERNAME=$smtpUsername
export MAILER_ENCRYPTION=$smtpEncryption
export MAILER_DSN=smtp://$smtpUsername:$smtpPassword@$smtpHost:$smtpPort
export laF_baseUrl=$HTTP_METHOD://$PUBLIC_URL

export MERCURE_JWT_SECRET=$MERCURE_JWT_SECRET

export PUBLIC_URL=$PUBLIC_URL
export OAUTH_KEYCLOAK_CLIENT_SECRET=$NEW_UUID
export HTTP_METHOD=$HTTP_METHOD

docker-compose -f docker-compose.test.yml build
docker-compose -f docker-compose.test.yml up -d
docker exec -d jitsi-admin_app-ja_1 bash /var/www/dockerupdate.sh