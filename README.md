# Employee Management System - Dockerized Web App

Sistem manajemen karyawan berbasis web yang dibangun dengan PHP, MySQL, dan Docker. Aplikasi ini dirancang untuk deployment di Google Cloud Platform Compute Engine.

## 🌟 Fitur Utama

### 📊 Dashboard Interaktif
- **Real-time Statistics**: Menampilkan statistik karyawan, departemen, proyek, dan kehadiran
- **Chart Visualizations**: Grafik distribusi karyawan per departemen dan tren kehadiran
- **Quick Actions**: Akses cepat ke fungsi-fungsi utama
- **Live Clock**: Jam real-time dengan tanggal

### 👥 Manajemen Karyawan
- **CRUD Operations**: Create, Read, Update, Delete data karyawan
- **Employee Profiles**: Profil lengkap dengan foto, informasi personal, dan jabatan
- **Department Assignment**: Penugasan karyawan ke departemen
- **Salary Management**: Manajemen gaji dan kompensasi
- **Status Tracking**: Status aktif, tidak aktif, atau terminated

### 🏢 Manajemen Departemen
- **Department Creation**: Buat dan kelola departemen
- **Manager Assignment**: Penugasan manager untuk setiap departemen
- **Employee Count**: Tracking jumlah karyawan per departemen
- **Department Statistics**: Statistik dan analisis departemen

### 📋 Manajemen Proyek
- **Project Lifecycle**: Kelola proyek dari planning hingga completion
- **Team Assignment**: Penugasan tim ke proyek
- **Budget Tracking**: Tracking anggaran proyek
- **Progress Monitoring**: Monitoring progress proyek
- **Deadline Management**: Manajemen deadline dan timeline

### ⏰ Sistem Kehadiran
- **Daily Attendance**: Pencatatan kehadiran harian
- **Check-in/Check-out**: Sistem masuk dan keluar
- **Attendance Status**: Status hadir, tidak hadir, terlambat, setengah hari
- **Working Hours**: Kalkulasi jam kerja
- **Attendance Reports**: Laporan kehadiran

### 📈 Reports & Analytics
- **Comprehensive Reports**: Laporan lengkap karyawan, departemen, dan proyek
- **Data Visualization**: Grafik dan chart untuk analisis data
- **Export Functionality**: Export laporan ke berbagai format
- **Statistical Analysis**: Analisis statistik mendalam

## 🛠️ Teknologi Stack

- **Backend**: PHP 8.1
- **Database**: MySQL 8.0
- **Web Server**: Apache 2.4
- **Frontend**: Bootstrap 5.3, JavaScript ES6
- **Charts**: Chart.js
- **Icons**: Font Awesome 6
- **Notifications**: SweetAlert2
- **Tables**: DataTables
- **Containerization**: Docker & Docker Compose

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Git
- Port 8080, 8081, 3306 available

### Installation

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd crud-app
   ```

2. **Build & Run with Docker Compose**
   ```bash
   docker-compose up -d --build
   ```

3. **Access Application**
   - **Main App**: http://localhost:8080
   - **phpMyAdmin**: http://localhost:8081
   - **Database**: localhost:3306

### Default Database
Database akan otomatis dibuat dengan sample data:
- 5 Departments
- 5 Sample Employees
- 3 Sample Projects
- Sample Attendance Records

## 📁 Struktur Proyek

```
crud-app/
├── docker-compose.yml          # Docker Compose configuration
├── Dockerfile                  # Docker image definition
├── apache-config.conf          # Apache virtual host configuration
├── README.md                   # Dokumentasi proyek
├── sql/
│   └── init.sql               # Database initialization script
└── src/                       # Source code aplikasi
    ├── index.php              # Entry point (redirect to dashboard)
    ├── dashboard.php          # Dashboard utama
    ├── employees.php          # Manajemen karyawan
    ├── departments.php        # Manajemen departemen
    ├── projects.php           # Manajemen proyek
    ├── attendance.php         # Sistem kehadiran
    ├── reports.php            # Reports & analytics
    ├── config/
    │   └── database.php       # Konfigurasi database
    ├── includes/
    │   ├── header.php         # Header template
    │   └── footer.php         # Footer template
    ├── assets/
    │   ├── css/
    │   │   └── style.css      # Custom CSS styles
    │   ├── js/
    │   │   └── app.js         # Custom JavaScript
    │   └── images/
    │       └── default-avatar.svg  # Default profile image
    └── api/
        ├── department-stats.php    # API untuk statistik departemen
        └── attendance-stats.php    # API untuk statistik kehadiran
```

## 🔧 Configuration

### Environment Variables
```bash
# Database Configuration
MYSQL_ROOT_PASSWORD=rootpassword
MYSQL_DATABASE=crud_db
MYSQL_USER=crud_user
MYSQL_PASSWORD=crud_password

# Application Ports
WEB_PORT=8080
PHPMYADMIN_PORT=8081
MYSQL_PORT=3306
```

### Apache Configuration
- **Document Root**: `/var/www/html`
- **Modules Enabled**: rewrite, headers, expires
- **Security Headers**: Configured
- **Gzip Compression**: Enabled
- **Cache Control**: Configured for static assets

## 🌐 Deployment di Google Cloud Platform

### 1. Persiapan GCP Compute Engine

```bash
# Create VM Instance
gcloud compute instances create employee-management-vm \
    --zone=asia-southeast2-a \
    --machine-type=e2-medium \
    --network-tier=PREMIUM \
    --maintenance-policy=MIGRATE \
    --image-family=ubuntu-2004-lts \
    --image-project=ubuntu-os-cloud \
    --boot-disk-size=20GB \
    --boot-disk-type=pd-balanced
```

### 2. Setup VM

```bash
# SSH ke VM
gcloud compute ssh employee-management-vm --zone=asia-southeast2-a

# Install Docker
sudo apt update
sudo apt install -y docker.io docker-compose
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker $USER
```

### 3. Deploy Aplikasi

```bash
# Clone repository
git clone <your-repository-url>
cd crud-app

# Run aplikasi
docker-compose up -d --build
```

### 4. Configure Firewall

```bash
# Allow HTTP traffic
gcloud compute firewall-rules create allow-employee-management-http \
    --allow tcp:8080 \
    --source-ranges 0.0.0.0/0 \
    --description "Allow HTTP access to Employee Management System"

# Allow phpMyAdmin (optional, for admin only)
gcloud compute firewall-rules create allow-phpmyadmin \
    --allow tcp:8081 \
    --source-ranges YOUR_IP_ADDRESS/32 \
    --description "Allow phpMyAdmin access"
```

## 💡 Fitur Advanced

### 1. Real-time Features
- Live clock dan tanggal
- Real-time statistics
- Instant notifications dengan SweetAlert2

### 2. Responsive Design
- Mobile-first approach
- Bootstrap 5 responsive grid
- Touch-friendly interface

### 3. Data Visualization
- Interactive charts dengan Chart.js
- Department distribution charts
- Attendance trend analysis

### 4. Security Features
- Input validation dan sanitization
- SQL injection protection dengan PDO prepared statements
- XSS protection
- Security headers konfigurasi

### 5. Performance Optimization
- Apache gzip compression
- Static asset caching
- Optimized database queries
- Lazy loading untuk images

## 🔍 API Endpoints

### Statistics APIs
- `GET /api/department-stats.php` - Statistik departemen
- `GET /api/attendance-stats.php` - Statistik kehadiran

### Features APIs
- `POST /api/attendance.php` - Check-in/check-out
- `POST /api/update-progress.php` - Update progress proyek

## 🎨 UI/UX Features

### Modern Design
- Gradient cards dan buttons
- Smooth animations dan transitions
- Custom color scheme
- Professional typography

### Interactive Elements
- Hover effects
- Loading states
- Progress bars
- Status badges

### Data Presentation
- Sortable dan searchable tables
- Filterable data
- Pagination
- Export functionality

## 📊 Database Schema

### Tabel Utama
1. **employees** - Data karyawan
2. **departments** - Data departemen
3. **projects** - Data proyek
4. **attendance** - Data kehadiran
5. **employee_projects** - Relasi karyawan-proyek

### Relasi Database
- Employees → Departments (many-to-one)
- Employees → Projects (many-to-many)
- Employees → Attendance (one-to-many)

## 🔧 Maintenance

### Backup Database
```bash
docker exec mysql-db mysqldump -u root -p crud_db > backup.sql
```

### Update Application
```bash
git pull origin main
docker-compose down
docker-compose up -d --build
```

### Monitor Logs
```bash
docker-compose logs -f web
docker-compose logs -f db
```

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

Untuk pertanyaan atau dukungan, silakan buat issue di repository ini atau hubungi tim development.

---

**Employee Management System** - Built with ❤️ using Docker, PHP, and Modern Web Technologies
