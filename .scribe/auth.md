# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_ACCESS_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Log in with <code>POST /api/v1/auth/login</code> and paste the <code>access_token</code> here. Tokens are JWTs that carry your abilities and expire after the configured <code>JWT_TTL</code> (60 minutes by default).
