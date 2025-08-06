#!/bin/bash

# Variables à adapter
ZIP_FILE="districlosnew.sql.zip"
DB_HOST="87.98.141.171"
DB_NAME="districlos_refonte"
DB_USER="districlos_refonte"
DB_PASS="TXM1qjk9teu_cfb9wxv"

# 1. Dézipper dans un dossier temporaire
mkdir -p tmp_sql_import
unzip "$ZIP_FILE" -d tmp_sql_import

# 2. Aller dans le dossier
cd tmp_sql_import || exit 1

# 3. Importer tous les fichiers .sql
for file in *.sql; do
  echo "Import de $file..."
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS"  "$DB_NAME" < "$file"
done

# 4. Nettoyer si tu veux
cd ..
rm -rf tmp_sql_import

echo "✅ Import terminé."
