#!/bin/bash

# Konfigurationsvariablen
REMOTE_USER="sterntal"
REMOTE_HOST="s083.cyon.net"
REMOTE_DIR="public_html/projekte-dev-divi/wp-content/plugins/simple-xml-sitemap-generator/"
SSH_KEY="$HOME/.ssh/my_cyon"
LOCAL_DIR=$(pwd)

# Debugging-Ausgabe
echo "Lokales Verzeichnis: $LOCAL_DIR"
echo "Remote-Verzeichnis: $REMOTE_DIR"
echo "SSH-Schlüssel: $SSH_KEY"

# Überprüfung auf vorhandene SSH-Schlüsseldatei
if [ ! -f "$SSH_KEY" ]; then
  echo "Fehler: SSH-Schlüssel $SSH_KEY nicht gefunden."
  exit 1
fi

# Wechsle in das lokale Verzeichnis und erstelle ein ZIP-Archiv
ZIP_NAME="plugin_upload.zip"
echo "Erstelle ZIP-Archiv $ZIP_NAME ..."
cd "$LOCAL_DIR"
#zip -r "$ZIP_NAME" . > /dev/null
zip -r "$ZIP_NAME" . -x ".git/*" "sync.sh" ".gitignore"


# Übertrage das ZIP-Archiv mit SCP
echo "Übertrage $ZIP_NAME nach $REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR ..."
scp -i "$SSH_KEY" "$ZIP_NAME" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR"

if [ $? -eq 0 ]; then
  echo "Upload erfolgreich abgeschlossen!"
else
  echo "Fehler beim Upload!"
  rm -f "$ZIP_NAME"
  exit 1
fi

# Entpacke das ZIP-Archiv auf dem Remote-Server
echo "Entpacke das ZIP-Archiv auf dem Server ..."
ssh -i "$SSH_KEY" "$REMOTE_USER@$REMOTE_HOST" "unzip -o $REMOTE_DIR$ZIP_NAME -d $REMOTE_DIR && rm $REMOTE_DIR$ZIP_NAME"

if [ $? -eq 0 ]; then
  echo "Dateien erfolgreich entpackt!"
else
  echo "Fehler beim Entpacken der Dateien!"
  exit 1
fi

# Lösche das lokale ZIP-Archiv
rm -f "$ZIP_NAME"
echo "Lokales ZIP-Archiv entfernt."
