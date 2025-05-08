## Analytics & Metrics Endpoints

### Endpoint Summary

| Path                        | Method | Description                  |
|-----------------------------|--------|------------------------------|
| /asap/v1/usage-metrics      | GET    | Retrieve usage metrics       |
| /asap/v1/cost-analysis      | GET    | Retrieve service cost data   |
| /asap/v1/service-tracking   | POST   | Submit service usage record  |

---

### GET `/asap/v1/usage-metrics`

**Description:**
Retrieve usage metrics for tracked services. Supports filtering by date, user, and service.

**Authentication:**
- Requires Better Auth session or valid token (see [better-auth-route-handling]).

**Query Parameters:**
| Name        | Type   | Required | Description                  |
|-------------|--------|----------|------------------------------|
| start_date  | string | No       | ISO date (YYYY-MM-DD)        |
| end_date    | string | No       | ISO date (YYYY-MM-DD)        |
| user_id     | int    | No       | Filter by user ID            |
| service     | string | No       | Filter by service name       |

**Example Request:**
```http
GET /asap/v1/usage-metrics?start_date=2025-05-01&end_date=2025-05-07&service=content_ingest
Authorization: Bearer <token>
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "service": "content_ingest",
      "usage": 42.5,
      "timestamp": "2025-05-07T15:00:00Z",
      "user_id": 1
    }
  ],
  "error": null
}
```

**Error Response:**
```json
{
  "success": false,
  "data": null,
  "error": "Authentication required."
}
```

**Implementation:**
- `wp-content/plugins/asapdigest-core/includes/api/class-usage-metrics-controller.php`
- [php-file-creation-protocol], [better-auth-route-handling]

---

### GET `/asap/v1/cost-analysis`

**Description:**
Retrieve cost analysis data for tracked services. Supports filtering by date and service.

**Authentication:**
- Requires Better Auth session or valid token.

**Query Parameters:**
| Name        | Type   | Required | Description                  |
|-------------|--------|----------|------------------------------|
| start_date  | string | No       | ISO date (YYYY-MM-DD)        |
| end_date    | string | No       | ISO date (YYYY-MM-DD)        |
| service     | string | No       | Filter by service name       |

**Example Request:**
```http
GET /asap/v1/cost-analysis?service=content_ingest
Authorization: Bearer <token>
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 55,
      "service": "content_ingest",
      "cost": 12.34,
      "timestamp": "2025-05-07T15:00:00Z"
    }
  ],
  "error": null
}
```

**Error Response:**
```json
{
  "success": false,
  "data": null,
  "error": "Authentication required."
}
```

**Implementation:**
- `wp-content/plugins/asapdigest-core/includes/api/class-usage-metrics-controller.php`
- [php-file-creation-protocol], [better-auth-route-handling]

---

### POST `/asap/v1/service-tracking`

**Description:**
Submit a new service usage record (and optional cost) for analytics tracking.

**Authentication:**
- Requires Better Auth session or valid token.

**Request Body (JSON):**
| Name      | Type   | Required | Description                  |
|-----------|--------|----------|------------------------------|
| service   | string | Yes      | Service name                 |
| usage     | float  | Yes      | Usage value (e.g., count)    |
| timestamp | string | Yes      | ISO datetime (YYYY-MM-DDTHH:MM:SSZ) |
| user_id   | int    | No       | User ID                      |
| cost      | float  | No       | Cost value (optional)        |

**Example Request:**
```http
POST /asap/v1/service-tracking
Authorization: Bearer <token>
Content-Type: application/json

{
  "service": "content_ingest",
  "usage": 10.5,
  "timestamp": "2025-05-07T15:00:00Z",
  "user_id": 1,
  "cost": 2.50
}
```

**Example Response:**
```json
{
  "success": true,
  "data": { "id": 456 },
  "error": null
}
```

**Error Response:**
```json
{
  "success": false,
  "data": null,
  "error": "Missing required fields: service, usage, timestamp"
}
```

**Implementation:**
- `wp-content/plugins/asapdigest-core/includes/api/class-usage-metrics-controller.php`
- [php-file-creation-protocol], [better-auth-route-handling], [server-memory-rules]

---

**Protocol Cross-References:**
- [php-file-creation-protocol]
- [better-auth-route-handling]
- [server-memory-rules]
- [rule-formatting-protocol]
- [meta-rule-execution-protocol]

--- 