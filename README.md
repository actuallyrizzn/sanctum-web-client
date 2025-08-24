# Web Chat Bridge for Broca2

A secure, API-first PHP web chat bridge that enables web-based chat integration with Broca2 agents without exposing the Sanctum server to the public internet.

## 🎯 Overview

This PHP application serves as a bridge between web chat widgets and Broca2 agents. It provides:

**Note:** The original PHP implementation has been moved to the `php/` folder and serves as a reference for the Flask port development. The system is being ported to Flask while maintaining 100% functional parity.

- **Zero New Attack Surface**: No new inbound ports on your Sanctum server
- **API-First Design**: Clean, well-documented REST API
- **Secure Communication**: Authentication and rate limiting
- **Real-time Chat**: Web widget with polling for responses
- **Admin Monitoring**: Session management and statistics

### 📋 Reference Implementation

This project serves as a **reference implementation** for the PHP web chat bridge. The provided interface and UI are examples that can be customized or replaced entirely, as long as the API contract remains intact.

**Key Points:**
- **API Contract**: The REST API endpoints and data formats must remain unchanged
- **UI Flexibility**: The web chat widget (`/web/chat.php`) and admin interface (`/web/admin.php`) can be completely redesigned
- **Customization**: You can modify the frontend styling, layout, and user experience
- **Integration**: Any custom UI must still communicate with the documented API endpoints
- **Authentication**: API key and admin password requirements must be maintained

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
├── php/                   # Original PHP implementation (reference)
│   ├── public/            # Web-accessible content (Nginx document root)
│   │   ├── index.php      # Main entry point
│   │   ├── api/           # API endpoints
│   │   │   └── v1/
│   │   │       └── index.php  # Single API entry point with querystring routing
│   │   ├── config/        # Configuration
│   │   │   ├── database.php   # Database setup and connection
│   │   │   └── settings.php   # API settings and rate limits
│   │   ├── includes/      # Shared functionality
│   │   │   ├── auth.php       # Authentication and rate limiting
│   │   │   └── api_response.php # Standardized API responses
│   │   └── web/           # Web interfaces
│   │       ├── chat.php       # Web chat widget
│   │       ├── admin.php      # Admin monitoring interface
│   │       └── assets/
│   │           ├── chat.js    # Frontend JavaScript
│   │           └── style.css  # Chat widget styling
│   └── db/                # Database files (outside public)
│       └── web_chat.db    # SQLite database (auto-created)
├── plugin_web_chat/       # Broca2 Plugin (Complete Implementation)
│   ├── plugin.py          # Main plugin class implementing Plugin interface
│   ├── api_client.py      # HTTP client for PHP API communication
│   ├── message_handler.py # Message processing and database integration
│   ├── settings.py        # Configuration management with dataclass
│   ├── test_plugin.py     # Full integration test
│   ├── simple_test.py     # Basic functionality test
│   ├── README.md          # Plugin documentation
│   └── BUILD_SUMMARY.md   # Implementation summary
├── db/                    # New database initialization (for Flask port)
│   └── init_database.sql  # SQL initialization script
├── docs/                  # Documentation
│   ├── port-docs/         # Flask port documentation
│   │   ├── flask-port-specification.md
│   │   ├── admin-interface-specification.md
│   │   ├── database-schema-and-migration.md
│   │   └── flask-implementation-guide.md
│   ├── plugin-development.md
│   ├── api-documentation.md
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

> **Note**: These interfaces are provided as **reference implementations**. You can customize or completely replace the UI while maintaining the same API contract.

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

### Customization Guidelines

When creating custom interfaces:

1. **Maintain API Contract**: Use the documented API endpoints exactly as specified
2. **Authentication**: Implement the same Bearer token authentication
3. **Error Handling**: Handle the same response formats and error codes
4. **Rate Limiting**: Respect the same rate limiting headers and responses
5. **CORS**: Ensure proper CORS configuration for cross-origin requests

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

## 🤖 Broca2 Plugin

This repository includes a complete Broca2 plugin implementation in the `plugin_web_chat/` directory. The plugin enables Broca2 agents to process web chat messages through a pull-based architecture.

### Plugin Features

- **Pull-based Architecture**: Plugin polls the PHP API for new messages
- **User Management**: Automatically creates Broca2 users and platform profiles for web chat visitors
- **Message Processing**: Integrates with Broca2's database and queue systems
- **Session Management**: Tracks web chat sessions and maintains conversation context
- **Error Handling**: Robust error handling with retry logic and exponential backoff
- **Configuration**: Environment-based configuration with validation

### Plugin Structure

```
plugin_web_chat/
├── plugin.py              # Main plugin class implementing Plugin interface
├── api_client.py          # HTTP client for PHP API communication
├── message_handler.py     # Message processing and database integration
├── settings.py            # Configuration management with dataclass
├── test_plugin.py         # Full integration test
├── simple_test.py         # Basic functionality test
├── README.md              # Plugin documentation
└── BUILD_SUMMARY.md       # Implementation summary
```

### Installation

1. **Copy the plugin** to your Broca2 plugins directory:
   ```bash
   cp -r plugin_web_chat/ /path/to/broca2/plugins/web_chat/
   ```

2. **Set environment variables**:
   ```bash
   # Required
   export WEB_CHAT_API_URL=http://localhost:8000
   export WEB_CHAT_API_KEY=StableGeniu$
   
   # Optional (with defaults)
   export WEB_CHAT_POLL_INTERVAL=5
   export WEB_CHAT_MAX_RETRIES=3
   export WEB_CHAT_RETRY_DELAY=10
   export WEB_CHAT_PLUGIN_NAME=web_chat
   export WEB_CHAT_PLATFORM_NAME=web_chat
   ```

3. **Test the plugin**:
   ```bash
   cd plugin_web_chat
   python simple_test.py
   ```

### Plugin Configuration

The plugin uses environment variables for configuration:

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `WEB_CHAT_API_URL` | Yes | - | URL of the PHP web chat bridge |
| `WEB_CHAT_API_KEY` | Yes | - | API key for authentication |
| `WEB_CHAT_POLL_INTERVAL` | No | 5 | Polling interval in seconds |
| `WEB_CHAT_MAX_RETRIES` | No | 3 | Maximum retry attempts |
| `WEB_CHAT_RETRY_DELAY` | No | 10 | Delay between retries in seconds |
| `WEB_CHAT_PLUGIN_NAME` | No | web_chat | Plugin name |
| `WEB_CHAT_PLATFORM_NAME` | No | web_chat | Platform name |

### How It Works

1. **Startup**: Plugin automatically starts when Broca2 loads
2. **Polling**: Continuously polls the PHP API for new messages
3. **Processing**: Messages are processed through Broca2's agent system
4. **Responses**: Agent responses are posted back to the web chat

### Testing

- **Basic Test**: `python simple_test.py` - Tests plugin structure and settings
- **Full Test**: `python test_plugin.py` - Tests complete integration (requires Broca2 environment)

### Documentation

- **Plugin README**: `plugin_web_chat/README.md` - Complete plugin documentation
- **Build Summary**: `plugin_web_chat/BUILD_SUMMARY.md` - Implementation details
- **API Documentation**: `docs/api-documentation.md` - PHP API reference

### Status

✅ **Complete Implementation**: All core components are implemented and tested
✅ **Broca2 Integration**: Follows Broca2 plugin patterns and interfaces
✅ **Documentation**: Comprehensive documentation and examples
⚠️ **Environment Setup**: Requires proper Broca2 environment for full testing

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

This project is licensed under the Creative Commons Attribution-ShareAlike 4.0 International License (CC-BY-SA 4.0).

You are free to:
- **Share** — copy and redistribute the material in any medium or format
- **Adapt** — remix, transform, and build upon the material for any purpose, even commercially

Under the following terms:
- **Attribution** — You must give appropriate credit, provide a link to the license, and indicate if changes were made
- **ShareAlike** — If you remix, transform, or build upon the material, you must distribute your contributions under the same license as the original

For more information, see: https://creativecommons.org/licenses/by-sa/4.0/ 