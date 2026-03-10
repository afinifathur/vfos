<#
.SYNOPSIS
    Sync Production Database (vfos) to Local Laragon
    
.DESCRIPTION
    Script ini akan:
    1. SSH ke server production (192.168.1.50)
    2. Export database vfos dari container Docker
    3. Download file backup ke lokal
    4. Import otomatis ke MySQL Laragon (Local)
    5. Membersihkan file temporary di server
    
.NOTES
    Server: veronica@192.168.1.50
    Local DB: root / (tanpa password)
#>

param(
    [int]$KeepDays = 7
)

# ============================================
# KONFIGURASI
# ============================================
$ServerUser = "veronica"
$ServerHost = "192.168.1.50"
$ServerSSH = "$ServerUser@$ServerHost"

# Path di Server
$ServerAppPath = "/srv/docker/apps/vfos"
$ServerBackupDir = "/tmp"

# Konfigurasi Lokal
$LocalDbUser = "root"
$LocalDbPass = ""  # Sesuai .env Laragon lokal (tanpa password default)
$LocalDbName = "vfos"
$LocalBackupDir = "C:\laragon\www\Vfos\backups"

# Konfigurasi Production (Sesuaikan jika nama container/password beda)
$ProdContainerName = "vfos-db"
$ProdDbUser = "vfos_user"
$ProdDbPass = "user_password_replace_me"
$ProdDbName = "vfos"

# Cari path MySQL Laragon secara dinamis (atau gunakan default jika tidak ketemu)
$LaragonMysql = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
if (!(Test-Path $LaragonMysql)) {
    # Coba cari versi lain di folder bin\mysql
    $MysqlPaths = Get-ChildItem "C:\laragon\bin\mysql\*\bin\mysql.exe"
    if ($MysqlPaths) {
        $LaragonMysql = $MysqlPaths[0].FullName
    }
    else {
        # Fallback to system PATH mysql
        $LaragonMysql = "mysql" 
    }
}

# ============================================
# FUNGSI
# ============================================
if (!(Test-Path "C:\laragon\www\Vfos\storage\logs")) {
    New-Item -ItemType Directory -Path "C:\laragon\www\Vfos\storage\logs" -Force | Out-Null
}
$DetailedLogFile = "C:\laragon\www\Vfos\storage\logs\sync_db_log.txt"

function Log-Message($level, $message) {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logEntry = "[$timestamp] [$level] $message"
    Write-Host $logEntry -ForegroundColor ($ifelse = if ($level -eq "ERROR") { "Red" } elseif ($level -eq "SUCCESS") { "Green" } elseif ($level -eq "WARNING") { "Yellow" } else { "Cyan" })
    Add-Content -Path $DetailedLogFile -Value $logEntry
}

function Log-Info($message) { Log-Message "INFO" $message }
function Log-Success($message) { Log-Message "SUCCESS" $message }
function Log-Error($message) { Log-Message "ERROR" $message }
function Log-Warning($message) { Log-Message "WARNING" $message }

# ============================================
# MAIN SCRIPT
# ============================================
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

Log-Info "============================================"
Log-Info "   PRODUCTION TO LOCAL DB SYNC SCRIPT (VFOS)"
Log-Info "============================================"

# Step 1: Buat direktori backup lokal
if (!(Test-Path $LocalBackupDir)) {
    New-Item -ItemType Directory -Path $LocalBackupDir -Force | Out-Null
}

$GlobalSuccess = $true
$backupFileName = "prod_$( $ProdDbName )_$timestamp.sql"
$serverBackupPath = "$ServerBackupDir/$backupFileName"
$localBackupPath = "$LocalBackupDir\$backupFileName"

Log-Info ""
Log-Info ">>> SINKRONISASI DATABASE: $ProdDbName"

# 1. Export di Server (Direct Docker Exec)
Log-Info "Mengekspor $ProdDbName dari container $ProdContainerName di server..."
# Check jika server butuh sudo docker atau tidak, biasanya tidak jika user dalam group docker
$exportCmd = "docker exec -i $ProdContainerName mysqldump -u$ProdDbUser -p$ProdDbPass $ProdDbName > $serverBackupPath"

ssh $ServerSSH $exportCmd 2>&1 | Add-Content -Path $DetailedLogFile
if ($LASTEXITCODE -ne 0) {
    Log-Error "Gagal ekspor $ProdDbName dari server. Pastikan nama container dan password benar."
    $GlobalSuccess = $false
} else {
    # 2. Download File
    Log-Info "Mendownload $backupFileName ke komputer lokal..."
    scp "${ServerSSH}:$serverBackupPath" $localBackupPath 2>&1 | Add-Content -Path $DetailedLogFile
    if ($LASTEXITCODE -ne 0) {
        Log-Error "Gagal mendownload file dari server."
        $GlobalSuccess = $false
    } else {
        # 3. Import ke Lokal
        Log-Info "Menginput data ke MySQL Lokal (Laragon)..."
        if (Test-Path $localBackupPath) {
            
            $dbPassFlag = if ([string]::IsNullOrEmpty($LocalDbPass)) { "" } else { "-p$LocalDbPass" }
            
            Log-Info "Menjalankan import menggunakan CMD redirection..."
            $importCmd = "cmd /c `"`"$LaragonMysql`" -u$LocalDbUser $dbPassFlag $LocalDbName < `"$localBackupPath`"`""
            Invoke-Expression $importCmd 2>&1 | Add-Content -Path $DetailedLogFile
            
            if ($LASTEXITCODE -eq 0) {
                Log-Success "Sinkronisasi $LocalDbName SELESAI!"
            }
            else {
                Log-Error "Gagal mengimport ke database lokal (Exit Code: $LASTEXITCODE)."
                $GlobalSuccess = $false
            }
        }
    }
}

# 4. Cleanup Server
Log-Info "Membersihkan file temporary di server..."
ssh $ServerSSH "rm -f $serverBackupPath" 2>&1 | Add-Content -Path $DetailedLogFile

# Step Final: Bersihkan backup lama di lokal
Log-Info ""
Log-Info "Membersihkan backup lama di lokal ( > $KeepDays hari)..."
Get-ChildItem "$LocalBackupDir\prod_*.sql" | Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$KeepDays) } | Remove-Item -Force

Log-Info ""
Log-Info "============================================"
if ($GlobalSuccess) {
    Log-Success "   SINKRONISASI SELESAI - DATA TERBARU!"
} else {
    Log-Error "   SINKRONISASI SELESAI DENGAN BEBERAPA KEGAGALAN."
    exit 1
}
Log-Info "============================================"
Log-Info ""
