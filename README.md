# Web Chat Bridge for Broca2

A secure, API-first PHP web chat bridge that enables web-based chat integration with Broca2 agents without exposing the Sanctum server to the public internet.

## 🎯 Overview

This PHP application serves as a bridge between web chat widgets and Broca2 agents. It provides:

- **Zero New Attack Surface**: No new inbound ports on your Sanctum server
- **API-First Design**: Clean, well-documented REST API
- **Secure Communication**: Authentication and rate limiting
- **Real-time Chat**: Web widget with polling for responses
- **Admin Monitoring**: Session management and statistics

## 🏗️ Architecture

```
┌─────────────────┐    HTTP Polling    ┌─────────────────┐
│   Broca2 Plugin │ ◄───────────────── │   PHP Web Chat  │
│   (web_chat)    │                    │   Bridge        │
│                 │    HTTP POST       │                 │
│                 │ ──────────────────► │                 │
└─────────────────┘                    └─────────────────┘
         │                                      │
         │                                      │
         ▼                                      ▼
┌─────────────────┐                    ┌─────────────────┐
│   Broca2 Core   │                    │   Web Chat      │
│   (Queue/Agent) │                    │   Widget        │
└─────────────────┘                    └─────────────────┘
```

## 📁 File Structure

```
sanctum-web-chat/
├── public/                # Web-accessible content (Nginx document root)
│   ├── index.php          # Main entry point
│   ├── api/               # API endpoints
│   │   └── v1/
│   │       └── index.php  # Single API entry point with querystring routing
│   ├── config/            # Configuration
│   │   ├── database.php   # Database setup and connection
│   │   └── settings.php   # API settings and rate limits
│   ├── includes/          # Shared functionality
│   │   ├── auth.php       # Authentication and rate limiting
│   │   └── api_response.php # Standardized API responses
│   └── web/               # Web interfaces
│       ├── chat.php       # Web chat widget
│       ├── admin.php      # Admin monitoring interface
│       └── assets/
│           ├── chat.js    # Frontend JavaScript
│           └── style.css  # Chat widget styling
├── db/                    # Database files (outside public)
│   └── web_chat.db       # SQLite database (auto-created)
├── docs/                  # Documentation
│   ├── plugin-development.md
│   └── project-plan.md
└── README.md             # This file
```

## 🚀 Quick Start

### 1. Prerequisites

- PHP 7.4 or higher
- SQLite support enabled
- Web server (Apache/Nginx) or PHP built-in server

### 2. Installation

1. **Clone or download** the sanctum-web-chat files to your web server
2. **Set permissions** for the database directory:
   ```bash
   chmod 755 db/
   chmod 644 db/web_chat.db  # if it exists
   ```

3. **Configure environment variables** (optional):
   ```bash
   export WEB_CHAT_API_KEY="your-secure-api-key"
   export WEB_CHAT_ADMIN_KEY="your-secure-admin-key"
   export WEB_CHAT_DEBUG="true"  # for development
   ```

### 3. Test the Installation

1. **Start the web server**:
   ```bash
   cd public
   php -S localhost:8080
   ```

2. **Access the web chat**:
   - Open `http://localhost:8080/` (redirects to chat)
   - Or directly: `http://localhost:8080/web/chat.php`
   - You should see the chat interface

3. **Test the API**:
    ```bash
    curl -X POST "http://localhost:8080/api/v1/index.php?action=messages" \
      -H "Content-Type: application/json" \
      -d '{"session_id":"test123","message":"Hello world"}'
    ```

## 🔌 API Reference

### Authentication

All API endpoints require authentication via Bearer token in the Authorization header:

```
Authorization: Bearer your-api-key
```

### Endpoints

#### POST /api/v1/index.php?action=messages
Send a message from the web chat widget.

**Request:**
```json
{
  "session_id": "string (required)",
  "message": "string (required)",
  "timestamp": "ISO 8601 timestamp (optional)"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Message received",
  "data": {
    "message_id": 123,
    "session_id": "test123",
    "timestamp": "2024-01-01T12:00:00Z"
  }
}
```

#### GET /api/v1/index.php?action=inbox
Retrieve unprocessed messages for Broca2 plugin (requires API key).

**Query Parameters:**
- `limit` (optional): Number of messages to return (default: 50, max: 100)
- `offset` (optional): Number of messages to skip (default: 0)
- `since` (optional): Only return messages after this timestamp

**Response:**
```json
{
  "success": true,
  "data": {
    "messages": [
      {
        "id": 123,
        "session_id": "test123",
        "message": "Hello world",
        "timestamp": "2024-01-01T12:00:00Z"
      }
    ],
    "pagination": {
      "total": 1,
      "limit": 50,
      "offset": 0,
      "has_more": false
    }
  }
}
```

#### POST /api/v1/index.php?action=outbox
Send agent response back to web chat (requires API key).

**Request:**
```json
{
  "session_id": "string (required)",
  "response": "string (required)",
  "message_id": "integer (optional)",
  "timestamp": "ISO 8601 timestamp (optional)"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Response sent successfully",
  "data": {
    "response_id": 456,
    "session_id": "test123",
    "timestamp": "2024-01-01T12:00:00Z"
  }
}
```

#### GET /api/v1/index.php?action=responses&session_id=xxx
Get responses for a specific session.

**Query Parameters:**
- `session_id` (required): Session ID to get responses for
- `since` (optional): Only return responses after this timestamp

**Response:**
```json
{
  "success": true,
  "data": {
    "session_id": "test123",
    "responses": [
      {
        "id": 456,
        "response": "Hello! How can I help you?",
        "timestamp": "2024-01-01T12:00:00Z",
        "message_id": 123
      }
    ]
  }
}
```

#### GET /api/v1/index.php?action=sessions
List active sessions (requires admin key).

**Query Parameters:**
- `limit` (optional): Number of sessions to return (default: 50, max: 100)
- `offset` (optional): Number of sessions to skip (default: 0)
- `active` (optional): Only return active sessions (default: true)

**Response:**
```json
{
  "success": true,
  "data": {
    "sessions": [
      {
        "id": "test123",
        "created_at": "2024-01-01T12:00:00Z",
        "last_active": "2024-01-01T12:05:00Z",
        "message_count": 2,
        "response_count": 1,
        "metadata": {
          "ip": "192.168.1.1",
          "user_agent": "Mozilla/5.0..."
        }
      }
    ],
    "pagination": {
      "total": 1,
      "limit": 50,
      "offset": 0,
      "has_more": false
    }
  }
}
```

## 🔧 Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `WEB_CHAT_API_KEY` | `your-api-key-here` | API key for plugin communication |
| `WEB_CHAT_ADMIN_KEY` | `your-admin-key-here` | Admin key for monitoring |
| `WEB_CHAT_DEBUG` | `false` | Enable debug logging |

### Rate Limiting

The API includes comprehensive rate limiting:

- **Overall limit**: 1000 requests per hour per IP
- **Per-endpoint limits**:
  - `/api/v1/index.php?action=messages`: 50 requests/hour
  - `/api/v1/index.php?action=responses`: 200 requests/hour
  - `/api/v1/index.php?action=inbox`: 120 requests/hour (for plugin)
  - `/api/v1/index.php?action=outbox`: 200 requests/hour (for plugin)
  - `/api/v1/index.php?action=sessions`: 20 requests/hour (admin)

### Security Features

- **Input validation**: All inputs are sanitized
- **Session management**: Automatic session timeout (24 hours)
- **Rate limiting**: Per-IP and per-endpoint limits
- **Authentication**: API key and admin key validation
- **CORS**: Proper CORS headers for web widget

## 🌐 Web Interfaces

### Chat Widget (`/web/chat.php`)

A modern, responsive chat interface that users can access directly. Features:

- Real-time message sending
- Automatic response polling
- Typing indicators
- Mobile-responsive design
- Session management

### Admin Interface (`/web/admin.php`)

A monitoring dashboard for administrators. Features:

- Active session monitoring
- Message and response statistics
- Real-time updates
- Session details

## 📊 Monitoring

### Logs

All API requests are logged to `logs/api.log` with the following information:

- Timestamp
- Request method and endpoint
- Client IP address
- Response status code
- Error details (if any)

### Database

The SQLite database (`database/web_chat.db`) contains:

- **web_chat_sessions**: Session information
- **web_chat_messages**: User messages
- **web_chat_responses**: Agent responses
- **rate_limits**: Rate limiting data

## 🔒 Security Considerations

### Production Deployment

1. **Use HTTPS**: Always use HTTPS in production
2. **Secure API Keys**: Use strong, unique API keys
3. **Database Security**: Ensure database file is not web-accessible
4. **Log Security**: Ensure log files are not web-accessible
5. **Rate Limiting**: Monitor and adjust rate limits as needed

### API Key Management

- Generate strong, random API keys
- Rotate keys regularly
- Use different keys for plugin and admin access
- Never commit API keys to version control

## 🧪 Testing

### Manual Testing

1. **Test web chat widget**:
   - Open `/web/chat.php`
   - Send a message
   - Verify it appears in the interface

2. **Test API endpoints**:
    ```bash
    # Send a message
    curl -X POST "http://localhost:8080/api/v1/index.php?action=messages" \
      -H "Content-Type: application/json" \
      -d '{"session_id":"test123","message":"Hello"}'
    
    # Check inbox (requires API key)
    curl -H "Authorization: Bearer your-api-key" \
      "http://localhost:8080/api/v1/index.php?action=inbox"
    ```

3. **Test admin interface**:
   - Open `/web/admin.php`
   - Enter admin key when prompted
   - Verify session data appears

### Automated Testing

Create test scripts to verify:

- API endpoint functionality
- Rate limiting behavior
- Authentication requirements
- Error handling
- Database operations

## 🚀 Next Steps

1. **Integrate with Broca2**: Create the Broca2 web chat plugin
2. **Deploy to production**: Set up proper hosting and SSL
3. **Monitor performance**: Set up monitoring and alerting
4. **Scale as needed**: Consider database optimization for high traffic

## 🤝 Contributing

This is part of the Broca2 ecosystem. For questions or contributions:

1. Follow the project's coding standards
2. Test thoroughly before submitting
3. Document any new features
4. Consider security implications

## 📄 License

This project is part of the Broca2 ecosystem and follows the same licensing terms. 