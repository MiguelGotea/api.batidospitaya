# Script para hacer commit y push rápido con timestamp
git add .
git commit -m "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
git pull origin main --rebase
git push
