# Fly.io GraphQL Schema Analysis

## Schema Discovery Process

Used GraphQL introspection query against `https://api.fly.io/graphql` to get the real schema.

## Key Findings

### Logs Implementation
- **WRONG**: `App.machines.nodes[].recentLogs` (doesn't exist)
- **CORRECT**: `App.allocations[].recentLogs(limit: Int = 10, range: Int = 300)`

### Structure
```graphql
type App {
  allocations(showCompleted: Boolean): [Allocation!]!
  machines: MachineConnection!
}

type Allocation {
  id: ID!
  idShort: ID!
  desiredStatus: String!
  privateIP: String
  region: String!
  recentLogs(limit: Int = 10, range: Int = 300): [LogEntry!]!
}

type LogEntry {
  id: String!
  instanceId: String!
  level: String!
  message: String!
  region: String!
  timestamp: ISO8601DateTime!
}
```

### Allocations vs Machines
- **Allocations**: Runtime instances (what we want for logs)
- **Machines**: Physical/virtual machine definitions
- Logs are attached to allocations, not machines

### Console Access
- `createConsoleSession` mutation not found in standard GraphQL
- Likely uses separate API endpoint or different auth mechanism
- Current implementation may need REST API fallback

## Updated Implementation

- Fixed `getLogs()` to use `App.allocations[].recentLogs`
- Added allocation context (ID, region, privateIP) to log entries
- Proper timestamp sorting using ISO8601 string comparison
- Added 1-hour range parameter (3600 seconds)