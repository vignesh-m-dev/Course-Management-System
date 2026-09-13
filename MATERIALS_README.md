# Course Materials System - Setup & Documentation

## 📋 Features Added

This system adds the ability to upload, manage, preview, and download course materials (documents, PDFs, images, videos, etc.).

## 🔧 Setup Instructions

### Step 1: Create Database Table
1. Open your browser and go to: `http://localhost/xampp/htdocs/course/create_materials_table.php`
2. The script will create the `course_materials` table and necessary directories
3. You should see a success message

### Step 2: Set Folder Permissions
Ensure the `uploads/materials` directory has write permissions:
- Windows: Right-click folder → Properties → Security → Edit → Allow Full Control
- Linux/Mac: Run `chmod 755 uploads/materials` in terminal

## 📁 New Files Created

1. **create_materials_table.php** - Setup script to create database table
2. **upload_material.php** - Admin panel to upload course materials
3. **download_material.php** - Handle file downloads with proper headers
4. **preview_material.php** - Preview PDFs, images, and text files
5. **delete_material.php** - Delete materials (admin only)

## 🎯 How to Use

### For Admins - Uploading Materials

1. Go to **Admin Panel** → Click **📚 Manage Materials**
2. Fill in the form:
   - **Select Course**: Choose which course this material belongs to
   - **Material Title**: Name of the material (e.g., "Lecture 1 - Introduction")
   - **Description**: Optional description of the content
   - **Select File**: Upload your file (PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, ZIP, JPG, PNG, GIF, MP4, etc.)
3. Click **Upload Material**

### For Students - Accessing Materials

1. Go to any course page (click "View Details" on a course card)
2. Scroll down to the **📚 Course Materials** section
3. You'll see all materials for that course with:
   - File icon (📕 PDF, 📗 DOC, 📊 XLSX, 🎥 Video, etc.)
   - File name and size
   - Upload date
4. Click:
   - **👁️ Preview** - View the file (images, PDFs, text files)
   - **⬇️ Download** - Download the file to your computer

### Supported File Types

**Documents:**
- PDF (.pdf)
- Word (.doc, .docx)
- Excel (.xls, .xlsx)
- PowerPoint (.ppt, .pptx)
- Text (.txt, .log, .csv)
- Archives (.zip, .rar)

**Media:**
- Images (.jpg, .jpeg, .png, .gif)
- Videos (.mp4, .avi, .mov)

**Max File Size**: 100 MB per file

## 📊 Database Schema

```sql
CREATE TABLE `course_materials` (
  `material_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`material_id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
);
```

## 🔐 Security Features

- Only admins can upload materials
- Files are stored with unique names to prevent conflicts
- File type validation before upload
- File size limit (100 MB)
- Proper MIME type headers for downloads
- SQL injection prevention with real_escape_string

## 🎨 User Interface

### Admin View
- Clean material upload form
- Table of all materials with quick actions
- Preview, download, and delete buttons
- File size and upload date display

### Student View
- Materials displayed as cards on course detail page
- File icons for different file types
- Quick preview and download buttons
- No clutter - only shows relevant materials

## 🚀 Usage Flow

```
Admin Dashboard
    ↓
📚 Manage Materials
    ↓
Upload Material
    ↓
Material saved to database & server
    ↓
Student views course
    ↓
Sees material cards with preview/download options
```

## 📝 Material Management

### To Edit a Material
Currently, you'll need to:
1. Delete the old material
2. Upload the updated version with a new name

### To Delete a Material
1. Go to **📚 Manage Materials**
2. Find the material in the table
3. Click **🗑️ Delete**
4. Confirm deletion

## 🐛 Troubleshooting

### Files not uploading
- Check folder permissions on `uploads/materials/`
- Ensure file size is under 100 MB
- Verify file type is in the allowed list

### Preview not working
- PDFs: Browser must support PDF viewing
- Images: Check file is a valid image
- Videos: HTML5 video player will attempt to play

### Database error
- Run `create_materials_table.php` again to recreate the table
- Check MySQL connection credentials

## 📞 Support

For issues or feature requests, check:
1. File upload permissions
2. Database connectivity
3. File size limits
4. Supported file types

---

**Version**: 1.0
**Last Updated**: February 25, 2026
