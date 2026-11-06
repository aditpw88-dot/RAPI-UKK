# Mobile API

Base URL: `http://<host-aplikasi>/api`. For an Android emulator, use the host address reachable from the emulator instead of `localhost`.

Send `Accept: application/json` with every request. Protected endpoints also require:

```http
Authorization: Bearer <token>
```

## Authentication

| Method | Endpoint | Access |
| --- | --- | --- |
| POST | `/login` | Public |
| POST | `/logout` | Authenticated |
| GET | `/user` | Authenticated |

Login accepts a username or email:

```json
{
  "login": "username-atau-email",
  "password": "password",
  "device_name": "Android"
}
```

Use the returned `token` as the bearer token. Logout revokes the current token.

## Rooms

| Method | Endpoint | Access |
| --- | --- | --- |
| GET | `/rooms` | Public; active rooms only |
| GET | `/rooms/{id}` | Public; active rooms only |
| POST | `/rooms` | Admin/Waka |
| PUT/PATCH | `/rooms/{id}` | Admin/Waka |
| DELETE | `/rooms/{id}` | Admin/Waka |

Room writes accept `room_name`, `capacity`, and `pic_id`. Updates may also set `is_active` to `true` or `false`.

## Events

| Method | Endpoint | Access |
| --- | --- | --- |
| GET | `/events` | Public; approved events only |
| GET | `/events/mine` | Authenticated; own requests |
| GET | `/events/pending` | PIC; requests for assigned rooms |
| POST | `/events` | Guru, Admin, or Waka |
| POST | `/events/{id}/approve` | PIC assigned to the room |
| POST | `/events/{id}/reject` | PIC assigned to the room |

Create an event with JSON fields `event_name`, `room_id`, `date` (`YYYY-MM-DD`), `start_session` (1-13), and `end_session` (2-13, not earlier than `start_session`). The date must be at least tomorrow. The server derives the times from the configured school schedule, checks room availability, and creates the request as `pending`.

Rejecting an event requires a JSON `rejection_reason`.

## Complaints

| Method | Endpoint | Access |
| --- | --- | --- |
| GET | `/complaints` | Authenticated; own complaints for Guru, assigned rooms for PIC, all for Admin/Waka |
| POST | `/complaints` | Guru, Admin, or Waka |
| PATCH | `/complaints/{id}/status` | PIC assigned to the room |

Create a complaint as `multipart/form-data` with `room_id`, `description`, and an image field named `photo` (maximum 5 MB). Status updates accept one of:

- `Belum Diperbaiki`
- `Menunggu Spare Part`
- `Sudah Diperbaiki`

For complaint photo URLs to work, configure the Laravel public storage link with `php artisan storage:link`.

## Responses

Successful responses use JSON and include `success` and, where applicable, `data`. Validation errors return HTTP 422; unauthenticated requests return 401; unauthorized roles return 403; schedule conflicts return 409.
