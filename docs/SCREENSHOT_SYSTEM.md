# Screenshot System Implementation

## Overview

Hệ thống screenshot cho phép nhận và lưu trữ ảnh chụp màn hình từ các máy tính client trong phòng. Hệ thống bao gồm:

1. **API endpoint** để nhận screenshot từ agent
2. **Database storage** để lưu trữ metadata
3. **File storage** để lưu trữ file ảnh
4. **Web interface** để xem và quản lý screenshots

## Architecture

### Database Schema

```sql
CREATE TABLE screenshots (
    id UUID PRIMARY KEY,
    command_id UUID REFERENCES commands(id) ON DELETE CASCADE,
    computer_id UUID REFERENCES computers(id) ON DELETE CASCADE,
    file_path VARCHAR NOT NULL,
    file_name VARCHAR NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type VARCHAR NOT NULL,
    taken_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### File Storage Structure

```
storage/app/public/screenshots/
├── 2025/
│   ├── 01/
│   │   ├── 15/
│   │   │   ├── screenshot_20250115143022_uuid1.png
│   │   │   └── screenshot_20250115143055_uuid2.jpg
│   │   └── 16/
│   └── 02/
└── 2024/
```

## API Endpoints

### 1. Upload Screenshot (Agent → Server)

**Endpoint:** `POST /api/agent/screenshot`

**Headers:**

```
Content-Type: multipart/form-data
```

**Request Body:**

```
command_id: UUID (required)
computer_id: UUID (required)
screenshot: File (required, max 10MB, image only)
taken_at: ISO 8601 datetime (required)
```

**Response:**

```json
{
    "message": "Screenshot uploaded successfully",
    "screenshot_id": "uuid",
    "file_url": "https://domain.com/storage/screenshots/2025/01/15/screenshot_xxx.png"
}
```

### 2. List Screenshots (Web → Server)

**Endpoint:** `GET /rooms/{room}/screenshots`

**Query Parameters:**

- `page`: Page number (default: 1)
- `computer_id`: Filter by computer ID
- `command_id`: Filter by command ID
- `from_date`: Filter from date (ISO 8601)
- `to_date`: Filter to date (ISO 8601)

**Response:**

```json
{
    "data": [
        {
            "id": "uuid",
            "command_id": "uuid",
            "computer_id": "uuid",
            "computer_name": "PC-01",
            "file_name": "screenshot_xxx.png",
            "file_size": 1024000,
            "file_size_formatted": "1.02 MB",
            "mime_type": "image/png",
            "file_url": "https://domain.com/storage/screenshots/...",
            "taken_at": "2025-01-15T14:30:22Z",
            "taken_at_formatted": "2025-01-15 14:30:22",
            "created_at": "2025-01-15T14:30:25Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 20,
        "total": 100
    }
}
```

### 3. Get Single Screenshot

**Endpoint:** `GET /rooms/{room}/screenshots/{screenshot}`

**Response:** Single screenshot object (same format as list item)

## Implementation Details

### Models

#### Screenshot Model

```php
class Screenshot extends Model
{
    protected $fillable = [
        'command_id',
        'computer_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'file_size' => 'integer',
    ];

    // Relationships
    public function command(): BelongsTo
    public function computer(): BelongsTo

    // Accessor
    public function getFileUrlAttribute(): string
}
```

### Controllers

#### AgentScreenshotController

- Handles screenshot uploads from agents
- Validates file format and size
- Stores file and creates database record
- Returns success/error response

#### RoomScreenshotController

- Handles web interface requests
- Lists screenshots with filtering and pagination
- Shows individual screenshot details
- Ensures room access permissions

### Actions

#### StoreScreenshotAction

- Generates unique filename
- Organizes files by date
- Stores file in public storage
- Creates database record
- Returns Screenshot model

### Request Validation

#### AgentScreenshotRequest

- Validates required fields
- Checks file format (image only)
- Limits file size (10MB max)
- Validates foreign key references

## Frontend Components

### ScreenshotGallery.vue

- Grid layout for screenshot thumbnails
- Filtering by computer, date range
- Search functionality
- Pagination controls
- Image preview modal
- Download functionality

### Screenshots.vue

- Page wrapper with breadcrumbs
- Integrates ScreenshotGallery component
- Room context

## Usage Examples

### Agent Implementation (Client Side)

```python
import requests
from datetime import datetime

def upload_screenshot(screenshot_file_path, command_id, computer_id):
    url = "https://your-server.com/api/agent/screenshot"

    with open(screenshot_file_path, 'rb') as f:
        files = {'screenshot': f}
        data = {
            'command_id': command_id,
            'computer_id': computer_id,
            'taken_at': datetime.now().isoformat()
        }

        response = requests.post(url, files=files, data=data)
        return response.json()
```

### Web Interface Usage

1. Navigate to room: `/rooms/{room_id}`
2. Click "Screenshots" tab or go to `/rooms/{room_id}/screenshots-page`
3. Use filters to find specific screenshots
4. Click thumbnail to view full size
5. Download screenshots as needed

## Security Considerations

1. **File Validation**: Only image files allowed
2. **Size Limits**: 10MB maximum file size
3. **Access Control**: Room-based permissions
4. **Storage**: Files stored outside web root
5. **Unique Names**: UUID-based filenames prevent conflicts

## Performance Considerations

1. **Pagination**: 20 screenshots per page
2. **Lazy Loading**: Images loaded on demand
3. **Thumbnails**: Consider generating thumbnails for better performance
4. **Cleanup**: Implement cleanup job for old screenshots
5. **CDN**: Consider CDN for file serving in production

## Monitoring and Maintenance

### Logs

- Screenshot upload attempts logged
- File storage errors logged
- Access attempts logged

### Cleanup

Consider implementing:

- Automatic deletion of screenshots older than X days
- Cleanup of orphaned files
- Database optimization

### Metrics

Track:

- Screenshot upload success rate
- Storage usage
- Popular screenshot times/computers
- Error rates

## Testing

### Unit Tests

```php
// Test screenshot creation
$screenshot = Screenshot::factory()->create();

// Test file URL generation
$this->assertStringContains('storage/screenshots', $screenshot->file_url);

// Test relationships
$this->assertInstanceOf(Computer::class, $screenshot->computer);
$this->assertInstanceOf(Command::class, $screenshot->command);
```

### Integration Tests

```php
// Test screenshot upload API
$response = $this->postJson('/api/agent/screenshot', [
    'command_id' => $command->id,
    'computer_id' => $computer->id,
    'screenshot' => UploadedFile::fake()->image('test.png'),
    'taken_at' => now()->toISOString(),
]);

$response->assertStatus(201);
```

## Future Enhancements

1. **Thumbnail Generation**: Auto-generate thumbnails for faster loading
2. **Image Compression**: Compress images to save storage
3. **Bulk Operations**: Download multiple screenshots as ZIP
4. **Annotations**: Allow adding notes to screenshots
5. **Comparison**: Side-by-side screenshot comparison
6. **Real-time**: WebSocket for real-time screenshot updates
7. **OCR**: Extract text from screenshots for search
8. **Analytics**: Screenshot analytics and reporting
