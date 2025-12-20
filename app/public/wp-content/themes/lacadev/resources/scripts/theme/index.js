/* eslint-disable no-unused-vars */
import '@images/favicon.ico';
import '@styles/theme';
import './pages/*.js';
import './ajax-search.js';
import gsap from 'gsap';
import Swup from 'swup';
import Swiper from 'swiper';

document.addEventListener( 'DOMContentLoaded', () => {
	const swup = new Swup();
	initializePageFeatures();

	swup.hooks.on( 'content:replace', () => {
		initializePageFeatures();
	} );
} );

function initializePageFeatures() {
	initHoverService();
	setupGsap404();
	initToggleDarkMode();
}

/**
 * Khởi tạo hoạt ảnh GSAP và AOS
 */
function initAnimations() {
	// GSAP
	gsap.registerPlugin( ScrollTrigger );
	gsap.from( '.block-title-scroll', {
		x: '50%',
		duration: 2,
		opacity: 0.3,
		scrollTrigger: {
			trigger: '.block-title-scroll',
			start: 'top 80%',
			end: 'bottom 20%',
			scrub: true,
		},
	} );

	//   // AOS
	//   AOS.init({
	//     duration: 400,
	//   });
}

function initMenu() {
	const $menuBtn = document.getElementById( 'btn-hamburger' );
	const navMenu = document.querySelector( 'nav.nav-menu' );

	if ( $menuBtn ) {
		$menuBtn.onclick = function ( e ) {
			const isExpanded = navMenu.classList.contains( 'actived' );

			// Update ARIA states
			$menuBtn.setAttribute( 'aria-expanded', ! isExpanded );
			$menuBtn.setAttribute(
				'aria-label',
				isExpanded ? 'Mở menu' : 'Đóng menu'
			);

			navMenu.classList.toggle( 'actived' );
			document.body.classList.toggle( 'overflow-hidden' );

			animatedMenu( this );
			e.preventDefault();
		};
	}
}

function animatedMenu( x ) {
	x.classList.toggle( 'animeOpenClose' );
}

function initSwiperSlider() {
	setTimeout( () => {
		new Swiper( '.sliders', {
			spaceBetween: 30,
			centeredSlides: true,
			effect: 'fade',
			speed: 1500,
			autoplay: {
				delay: 5000,
				disableOnInteraction: false,
			},
		} );
	}, 500 );
}

/**
 * hide/show header when scrolling
 */
function setupHideHeaderOnScroll() {
	let lastScrollTop = 0;
	const header = document.getElementById( 'header' );
	let scrollTimeout;

	window.addEventListener( 'scroll', () => {
		clearTimeout( scrollTimeout );

		const currentScrollTop =
			window.pageYOffset || document.documentElement.scrollTop;

		if ( currentScrollTop > lastScrollTop ) {
			header.classList.add( 'hidden' );
		} else {
			header.classList.add( 'hidden' );
		}

		lastScrollTop = currentScrollTop <= 0 ? 0 : currentScrollTop;

		scrollTimeout = setTimeout( () => {
			header.classList.remove( 'hidden' );
		}, 500 );
	} );
}

function setupGsap404() {
	gsap.set( 'svg', { visibility: 'visible' } );

	gsap.to( '#spaceman', {
		y: 5,
		rotation: 2,
		yoyo: true,
		repeat: -1,
		ease: 'sine.inOut',
		duration: 1,
	} );

	gsap.to( '#starsBig line', {
		rotation: 'random(-30,30)',
		transformOrigin: '50% 50%',
		yoyo: true,
		repeat: -1,
		ease: 'sine.inOut',
	} );

	gsap.fromTo(
		'#starsSmall g',
		{ scale: 0 },
		{
			scale: 1,
			transformOrigin: '50% 50%',
			yoyo: true,
			repeat: -1,
			stagger: 0.1,
		}
	);

	gsap.to( '#circlesSmall circle', {
		y: -4,
		yoyo: true,
		duration: 1,
		ease: 'sine.inOut',
		repeat: -1,
	} );

	gsap.to( '#circlesBig circle', {
		y: -2,
		yoyo: true,
		duration: 1,
		ease: 'sine.inOut',
		repeat: -1,
	} );

	gsap.set( '#glassShine', { x: -68 } );
	gsap.to( '#glassShine', {
		x: 80,
		duration: 2,
		rotation: -30,
		ease: 'expo.inOut',
		transformOrigin: '50% 50%',
		repeat: -1,
		repeatDelay: 8,
		delay: 2,
	} );
}
