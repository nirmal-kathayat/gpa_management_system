# GPA Conversion System

A comprehensive Laravel-based Grade Point Average (GPA) conversion system designed specifically for Nepalese schools. This system allows schools to manage students, generate report cards, and convert grades to GPA format with PDF export functionality.

## Features

### 🎓 **Student Management**
- Complete student information management
- Student profiles with personal details
- Class and section organization
- Roll number tracking

### 🏫 **School Management**
- Multi-school support
- School profiles with contact information
- Logo upload functionality
- School-specific user access

### 📚 **Subject Management**
- Customizable subject creation
- Subject codes and marking schemes
- Full marks and pass marks configuration
- Active/inactive subject status

### 📊 **Grade System**
- Nepalese grading system (A+ to NG)
- Grade point calculation (0.0 to 4.0 scale)
- Customizable grade boundaries
- Automatic GPA calculation

### 📋 **Report Card Generation**
- Traditional Nepalese report card format
- Multiple examination types support:
  - 1st Terminal Examination
  - 2nd Terminal Examination
  - Final Terminal Examination
  - Pre-Board Examination
- Theory and practical marks separation
- Behavioral assessment tracking
- Attendance management

### 📄 **PDF Export**
- Professional PDF report cards
- Print-ready format
- School letterhead integration
- Digital signatures section

### 👥 **User Management**
- Role-based access control:
  - **Admin**: Full system access
  - **Teacher**: School-specific access
  - **Staff**: Limited access
- User authentication and authorization
- Profile management

### 📈 **Dashboard & Analytics**
- System overview statistics
- Recent reports tracking
- Grade distribution charts
- Top performing students
- Monthly report creation stats

### 📤 **Bulk Import**
- CSV-based student import
- Template download
- Bulk data processing
- Error handling and validation

## Installation

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Node.js & NPM (for frontend assets)

### Step 1: Clone the Repository
```bash
git clone <repository-url>
cd gpa-system
