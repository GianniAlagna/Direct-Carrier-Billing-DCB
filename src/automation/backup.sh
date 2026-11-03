#!/bin/sh
# QTPass - nightly backup (central server). docs/04 section 6.
# Consistent InnoDB dump, encrypted, copied offsite; 30 days retention.
set -e

DATE=$(date +%Y%m%d-%H%M)
DIR=/var/backups/qtpass
OFFSITE=backup@offsite.qtpass.example:/srv/backup/qtpass
mkdir -p "$DIR"

mysqldump --defaults-file=/etc/qtpass/my.cnf --single-transaction --routines \
          --master-data=2 qtpass | gzip -9 > "$DIR/qtpass-$DATE.sql.gz"

# Symmetric encryption with a key file kept off the backup host
gpg --batch --yes --symmetric --cipher-algo AES256 \
    --passphrase-file /etc/qtpass/backup.key \
    -o "$DIR/qtpass-$DATE.sql.gz.gpg" "$DIR/qtpass-$DATE.sql.gz"
rm "$DIR/qtpass-$DATE.sql.gz"

# Member photos (incremental)
tar czf "$DIR/photos-$DATE.tgz" -C /var/qtpass photos

scp -q "$DIR/qtpass-$DATE.sql.gz.gpg" "$DIR/photos-$DATE.tgz" "$OFFSITE/"

find "$DIR" -type f -mtime +30 -delete
echo "$(date) backup ok $DATE"
