#!/bin/bash

# Define paths
SRC_DIR="./app"
DEST_DIR="/var/www"
BACKUP_DIR="/var/hello-php/backup-www"

# Command-line options
RESTART_SERVER=false

# Parse command-line options
while getopts "r" opt; do
  case ${opt} in
    r )
      RESTART_SERVER=true
      ;;
    * )
      echo "Invalid option. Usage: deploy.sh [-r]"
      exit 1
      ;;
  esac
done

# Ensure script is run with elevated privileges
if [ "$EUID" -ne 0 ]; then
  echo "Please run as root or use sudo"
  exit 1
fi

echo "Starting deployment..."

# Backup existing site
echo "Backing up existing site to $BACKUP_DIR..."
rm -rf "$BACKUP_DIR"
cp -r "$DEST_DIR" "$BACKUP_DIR"

# Remove old files
echo "Removing old files from $DEST_DIR..."
rm -rf "$DEST_DIR"/*

# Copy new files
echo "Copying new files from $SRC_DIR to $DEST_DIR..."
cp -r "$SRC_DIR"/* "$DEST_DIR"

# Set correct permissions
echo "Setting correct permissions..."
chown -R www-data:www-data "$DEST_DIR"
find "$DEST_DIR" -type d -exec chmod 755 {} \;
find "$DEST_DIR" -type f -exec chmod 644 {} \;
# Support Uploads
# chmod -R 775 "$DEST_DIR/html/uploads"

# Restart web server (if needed)
if $RESTART_SERVER; then
  echo "Restarting web server..."
  systemctl restart apache2
fi

echo "Deployment complete!"
