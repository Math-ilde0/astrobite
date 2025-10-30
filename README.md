Setup OAuth (Google & Facebook)

1) Database
- Run: sql/migrate-oauth.sql after schema.sql

2) Credentials
- Google: Create OAuth 2.0 Client ID (Web) in Google Cloud Console
  - Authorized redirect URI: http(s)://YOUR_HOST/PATH/google-login/callback.php
- Facebook: Create App (Facebook Login)
  - Valid OAuth Redirect URIs: http(s)://YOUR_HOST/PATH/facebook-login/callback.php

3) Configure
- Set env vars (recommended) before Apache/MAMP or edit includes/oauth.php directly:
  - GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET
  - FACEBOOK_CLIENT_ID, FACEBOOK_CLIENT_SECRET

4) Use
- Google button goes to /login-google.php
- Facebook button goes to /login-facebook.php


