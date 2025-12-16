/* eslint-disable no-console, no-unused-vars, no-alert */
// eslint-disable-next-line no-unused-vars
import '@styles/login';

document.addEventListener( 'DOMContentLoaded', () => {
	const loginHeaderLink = document.querySelector( '#login h1 a' );
	if ( loginHeaderLink ) {
		loginHeaderLink.setAttribute( 'href', 'https://lacadev.com/' );
		loginHeaderLink.setAttribute( 'target', '_blank' );
	}

	document
		.getElementById( 'user_login' )
		.setAttribute( 'placeholder', 'Username or Email Address' );
	document
		.getElementById( 'user_pass' )
		.setAttribute( 'placeholder', 'Password' );

	// create div class welcome
	const welcomeDiv = document.createElement( 'div' );
	welcomeDiv.className = 'welcome';
	welcomeDiv.textContent = 'Welcome to our website';

	// insert after logo
	const loginForm = document.getElementById( 'login' );
	const logo = document.querySelector( '#login h1' );
	if ( logo ) {
		logo.insertAdjacentElement( 'afterend', welcomeDiv );
	}
} );
