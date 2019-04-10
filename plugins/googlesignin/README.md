#Google Sign In

This plugin leverages Vanilla's core OAuth2 class to allow users to log into their Vanilla application through Google's "Google Sign In". 

Google has an OpenID Connect compliant workflow for authenticating users. For a list of all the current endpoints, scopes, claims, etc. visit [Google's discovery endpoint](https://accounts.google.com/.well-known/openid-configuration).

For more information about the Google Sign In OpenID Connect workflow go to [developers.google.com/identity/protocols/OpenIDConnect](https://developers.google.com/identity/protocols/OpenIDConnect).

## Google Sign In Feature
- **Summary:** This plugin adds a Sign In button to a Vanilla application to allow users to sign in using their Google (gmail) credentials.
- **Use case:** Single Sign On.
- **Description:** 
	- This plugin:
		- Extends the GDN_OAuth2 class to follow a standard OpenID Connect workflow to authenticate users.
		- 'Hardcodes' as constants of all endpoints and scopes:
			- **AUTHORIZEURL**: https://accounts.google.com/o/oauth2/v2/auth.
			- **TOKENURL**: https://oauth2.googleapis.com/token.
			- **PROFILEURL**: https://openidconnect.googleapis.com/v1/userinfo.
			- **SCOPES**: email openid profile.
		- Translates claims returned from Google to match what Vanilla's authenticator uses.
			- **sub** becomes UniqueID.
			- **name** becomes Name (the display name). 
		- Creates a settings form in the dashboard that will allow administrators to save the Google OAuth 2.0 client ID, Client secret, and/or set the Google Sign In method as the only sign in method for the Vanilla application.
		- Creates a button for the public facing Vanilla application to click to sign in through Google.
- **Setup steps:**
	1. Turn on the Google Sign In plugin.
	2. Enter the Client ID and Client secret from a Google Oauth 2.0 application.
- **QA steps:**
   - Using Google Sign In as exclusive authentication provider:
	 1. Turn on and configure Google Sign In plugin.
	 2. On the Google Sign In settings form in the dashboard, check the "Make this connection your default signin method." checkbox.
	 3. Signout of the dashboard.
	 4. The native Vanilla Sign In button should be available, not the Google Sign In button.
	 5. Click on Sign In. You should be redirected to Google to sign in.
	 6. Complete the process of adding your username and password.
	 7. If this is a new user you should be logged in automatically with your avatar and name from Google displaying.
	 8. If this is not a new user (if a user already exists on your Vanilla application with the same email address) and if in your config 'Garden.Registration.AutoConnect' is **not** set to TRUE, you will be prompted to put in the password of the existing user.

   - Using Google Sign In in conjunction with other sign in methods:
	 1. Turn on and configure Google Sign In plugin.
	 2. On the Google Sign In settings form in the dashboard, make sure the "Make this connection your default signin method." checkbox is **not** checked.
	 3. Signout of the dashboard.
	 4. The native Vanilla Sign In button should be available as well as the Google Sign In button.
	 5. Click on the Google Sign In. You should be redirected to Google to sign in.
	 6. Complete the process of adding your username and password.
	 7. If this is a new user you should be logged in automatically with your avatar and name from Google displaying.
	 8. If this is not a new user (if a user already exists on your Vanilla application with the same email address) and if in your config 'Garden.Registration.AutoConnect' is **not** set to TRUE, you will be prompted to put in the password of the existing user.

- **Pitfalls and Gotchas:**
	- There are many possible pitfalls when setting up SSO. Most of the difficulty will be in setting up the Google Sign In app in [**Google's developer console**](https://console.developer.google.com). Among the things to watch for:
		- Google will only allow to be configured to a legitimate TLD (no `.localhost`)
		- You have to include the exact `redirect_uri`. If your local set up is not HTTPS, it will send a `redirect_uri` that does not include the https schema and will be rejected by the Google app.