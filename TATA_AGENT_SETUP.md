# Tata TeleServices Agent Creation Feature

## Overview
This feature allows administrators to create agents in the Tata TeleServices system directly from the admin panel. When a user is created in the CRM system, administrators can now create a corresponding agent in the Tata system with a single click.

## Setup Instructions

### 1. Environment Configuration
Add the following environment variables to your `.env` file:

```env
TATA_API_TOKEN=your_tata_api_token_here
TATA_CALLER_IDS=1,2,3
TATA_USER_ROLE=2
```

**Note**: You need to get the valid caller IDs and user role ID from your Tata TeleServices account. You can find these in your Tata account dashboard or by calling their support.

### 2. Database Migration
The feature includes a migration that adds a `tata_agent_id` field to the users table. This field stores the agent ID returned by the Tata API.

### 3. API Configuration
The Tata API endpoint used is:
- **URL**: `https://api-smartflo.tatateleservices.com/v1/user`
- **Method**: POST
- **Authentication**: Bearer token

## How to Use

### For Administrators:
1. Navigate to the Users management page in the admin panel
2. Find the user you want to create a Tata agent for
3. Click the "Create Tata Agent" button (phone icon) in the Actions column
4. Confirm the action in the popup dialog
5. The system will automatically:
   - Create the agent in Tata TeleServices system
   - Set the password to the user's email address
   - Configure basic agent settings
   - Store the Tata agent ID in the local database

### Agent Configuration Details:
- **Name**: User's full name (first name + last name)
- **Email**: User's email address
- **Mobile**: User's mobile number
- **Login ID**: User's email address
- **Password**: User's email address (as requested)
- **Status**: Enabled
- **Web Login**: Blocked
- **Login-based Calling**: Enabled
- **Route Call Through**: Agent only
- **Extension**: Not assigned

## Features Implemented

### Backend:
- `TataService::createAgent()` method for API communication
- `UserController::createAgent()` method for handling requests
- Database migration for `tata_agent_id` field
- Route configuration for the create agent endpoint

### Frontend:
- "Create Tata Agent" button in the users table
- AJAX functionality for seamless user experience
- Loading states and success/error notifications
- Button state management (disabled after successful creation)

### Security:
- CSRF protection
- Input validation
- Error handling and logging
- Duplicate agent creation prevention

## API Request Format

The system sends the following data to the Tata API:

```json
{
  "create_agent": true,
  "status": true,
  "block_web_login": true,
  "login_based_calling": true,
  "name": "User Full Name",
  "number": "user_mobile_number",
  "email": "user@email.com",
  "login_id": "user@email.com",
  "user_role": 1,
  "password": "User123!",
  "caller_id": [],
  "route_call_through": 0,
  "assign_extension": false
}
```

## Error Handling

The system handles various error scenarios:
- Missing API token configuration
- Network connectivity issues
- API response errors
- Duplicate agent creation attempts
- Invalid user data

All errors are logged and displayed to the user with appropriate messages.

## Notes

- The feature prevents duplicate agent creation by checking the `tata_agent_id` field
- The system uses the user's email as both login ID and password as requested
- All API calls are logged for debugging and monitoring purposes
- The feature integrates seamlessly with the existing user management system
