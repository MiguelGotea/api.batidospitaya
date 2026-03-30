# Auto-navegar a la raíz del repositorio (GPS Interno)
Set-Location $PSScriptRoot
Set-Location ..

# Script para hacer commit y push rápido con timestamp
git add .
git commit -m "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" 2>$null
git pull origin main --rebase
git push
