<?php
/**
 * Template Name: Part Time Delivery Driver
 * 
 */
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<style>
	 @font-face {
    font-family: 'LT Highlight ExtraBold';
    font-style: normal;
    font-weight: normal;
    src: local('LT Highlight ExtraBold'), url('https://woocommerce-401163-4488997.cloudwaysapps.com/wp-content/themes/hello-theme-child/assets/fonts/lt-highlight-webfont/LT Highlight.woff') format('woff');
    }
	* {box-sizing: border-box;color:#fff}
	body {margin: 0;padding: 0;box-sizing: border-box;background: #000;color:#fff}
	html {scroll-behavior: smooth;}
	.job-delivery-rider-main .success-message {color: green;display: none; font-family: 'Montserrat';font-weight: 600;font-size: clamp(1.25rem, 0.905rem + 0.96vw, 1.625rem);margin: 0 0 30px;}
	main.job-delivery-rider-main {background: url("/wp-content/uploads/2024/06/layout-bg.png");background-position: center;background-size: cover;background-repeat: no-repeat;}
	.job-delivery-rider-main .supavapes-link-section {padding: 20px 0;width: 100%;margin: 0 auto;position: sticky;background: #000;height: auto;top: 0;z-index: 99;margin-bottom:30px;}
	.job-delivery-rider-main  .supavapes-link-section.sticky{box-shadow: 0 0 20px rgba(236, 78, 52, 0.4);}
	.job-delivery-rider-main .supavapes-link-section a.supavapes-link {width: fit-content;padding: 15px 30px;border: 1px solid #EC4E34;background-color: #EC4E34;display: flex;align-items: center;gap: 12px;border-radius: 50px;font-size: 18px;font-weight: 500;font-family: 'Montserrat';color: #fff;text-decoration: none;transition: all 0.3s ease-in-out;}
	input:-webkit-autofill,
	input:-webkit-autofill:hover,
	input:-webkit-autofill:focus,
	input:-webkit-autofill:active {
		transition: background-color 5000s ease-in-out 0s !important;
		-webkit-text-fill-color: #fff !important;
	}
	h3.requirements-description-title {margin: 0 0 30px;color: #fff;font-family: 'Montserrat';font-weight: 600;font-size: clamp(1.25rem, 0.675rem + 1.6vw, 1.875rem);}
	ul.requirements-description-list {padding: 0;margin: 0 0 60px;display: flex;flex-direction: column;gap: 20px;list-style-type: none;}
	ul.requirements-description-list li {display: flex;flex-direction: row;gap: 15px;}
	ul.requirements-description-list li svg {margin-top: 7px;}
	ul.requirements-description-list li span {width: calc(100% - 34px);color: #fff;font-family: 'Montserrat';font-size: 18px;font-weight: 500;line-height: 32px;}	
	h4.please-note {margin: 0 0 15px;color: #fff;font-family: 'Montserrat';font-weight: 600;font-size: clamp(1.25rem, 0.905rem + 0.96vw, 1.625rem);}
	.job-delivery-rider-main .job-banner{width: 100%;margin-bottom:clamp(5rem, -0.175rem + 14.4vw, 10.625rem);margin-top:30px;}
	.job-delivery-rider-main .job-container {width: 100%;max-width: 1240px;padding:0 20px;margin: 0 auto;}
	.job-delivery-rider-main .job-banner-details {color: #fff;display: flex;flex-direction: row;gap: 70px;align-items: center;}
	.job-delivery-rider-main .job-banner-left {width: 100%;max-width: calc(50% - 35px);display: flex;flex-direction: column;}
	.job-delivery-rider-main .job-banner-right {width: 100%;max-width: calc(50% - 35px);display: flex;justify-content: flex-end;}
	.job-delivery-rider-main .job-banner-right img{width: 100%;height:auto;max-width:490px;}
	.job-delivery-rider-main .job-banner-subtitle {font-family: "Roboto", sans-serif;margin: 0 0 10px;letter-spacing: 3px;font-weight: 500;text-transform: uppercase;color: #fff;font-size: 22px;}
	.job-delivery-rider-main .job-banner-title {font-family: 'LT Highlight ExtraBold' !important;margin: 0 0 10px;font-size: clamp(1.5rem, -0.34rem + 5.12vw, 3.5rem);color: #fff;line-height:1;}
	.job-delivery-rider-main h1.job-banner-main-title span{font-family: 'LT Highlight ExtraBold' !important;color: #EC4E34;}
	.job-delivery-rider-main h1.job-banner-main-title {font-family: 'LT Highlight ExtraBold' !important;margin: 0 0 5px;font-size: clamp(3rem, -0.565rem + 9.92vw, 6.875rem);color: #fff;line-height:1;}
	.job-delivery-rider-main p.job-banner-text {margin: 0 0 45px;font-family: Montserrat;font-size: clamp(1rem, 0.77rem + 0.64vw, 1.25rem);line-height: 30px;font-weight: 400;letter-spacing: 0.5;color: #fff;}
	.job-delivery-rider-main a.job-banner-button {display: flex;width: fit-content;background: transparent;border: 1px solid #EC4E34;padding: 19px 36px;border-radius: 60px;font-size: 18px ;font-family: 'Montserrat';font-weight: 600;text-transform: capitalize;line-height: 26px;color: #fff;text-decoration: none;}
	.job-delivery-rider-main a.job-banner-button:hover {background-color: #EC4E34;transition: all 0.3s ease-in-out;}
	button{cursor: pointer;}
	.job-banner-buttons {display: flex;gap: 20px;margin-bottom: 20px;flex-flow: row wrap;align-items: flex-start;}
	button.share-banner-button.share-btn svg * {fill: #fff;}
	.job-delivery-rider-main .share-banner-button {display: flex;width: fit-content;background: transparent;border: 1px solid #EC4E34;background-color: #EC4E34;padding: 19px 36px;border-radius: 60px;font-size: 18px ;font-family: 'Montserrat';font-weight: 600;text-transform: capitalize;line-height: 26px;color: #fff;text-decoration: none;}
	.job-delivery-rider-main .share-banner-button:hover {background-color: transparent;transition: all 0.3s ease-in-out;}
	.job-banner-share {display: flex;gap: 10px;flex-direction: column;}
	.share-option {display: flex;visibility: hidden;gap: 10px;}
	.share-option.active {visibility: visible;}
	a.share-icon {background-color: #fff;padding: 10px;border-radius: 50%;width: 40px;height: 40px;display: flex;align-items: center;justify-content: center;transition: all 0.3s ease-in-out;}
	a.share-icon:hover{background-color: #EC4E34;transition: all 0.3s ease-in-out;}
	a.share-icon:hover svg *{fill: #fff;}
	a.share-icon svg {width: 20px;height: 20px;	}
	.job-delivery-rider-main .job-banner-mobile-img{display:none}
	.job-delivery-rider-main .job-banner-mobile-img img{max-width: 380px; width: 100%;}
	span.error-message {color: #ff0000;font-family: 'Montserrat';padding-left: 20px;}
	.job-delivery-rider-main section.job-requirements-ighlights {width: 100%;}
	.job-delivery-rider-main .requirements-ighlights-details {display: flex;flex-direction: row;gap: 70px;}
	.job-delivery-rider-main .requirements-ighlights-box {display: flex;flex-direction: column;border: 1px solid #EC4E34;flex: 1;border-radius: 12px;overflow: hidden;}
	.job-delivery-rider-main h3.requirements-ighlights-box-title {padding: 20px 30px;margin: 0;color: #fff;background: #EC4E34;font-family: 'Montserrat';font-weight: 600;font-size: clamp(1.25rem, 0.675rem + 1.6vw, 1.875rem);}
	.job-delivery-rider-main .requirements-ighlights-box ul {padding: 30px;margin: 0;display: flex;flex-direction: column;gap: 20px;}
	.job-delivery-rider-main .requirements-ighlights-box ul li{display: flex;flex-direction: row;gap: 15px;}
	.job-delivery-rider-main .requirements-ighlights-box ul li svg {margin-top: 7px;}
	.job-delivery-rider-main .requirements-ighlights-box ul li span{width: calc(100% - 34px);color: #fff;font-family: 'Montserrat';font-size: 18px;font-weight: 500;line-height: 32px;}
	.job-delivery-rider-main .job-apply-form-section {width: 100%;margin-top: clamp(1.875rem, 0.15rem + 4.8vw, 3.75rem);}
	.job-delivery-rider-main .job-apply-form-section p{width: 100%;margin:0 0 20px;color: #fff;font-family: 'Montserrat';font-size: 18px;font-weight: 400;line-height: 26px;}
	.job-delivery-rider-main form.job-apply-form {margin-top: clamp(1.875rem, -1rem + 8vw, 5rem);display: flex;flex-direction: column;gap: clamp(1.875rem, -1rem + 8vw, 5rem);margin-bottom:100px;}
	.job-delivery-rider-main h3.job-apply-form-title {font-family: 'Montserrat';font-weight: 600;font-size: clamp(1.25rem, 0.905rem + 0.96vw, 1.625rem);margin: 0 0 30px;color:#fff;}
	.job-delivery-rider-main .job-personal-info {padding: 40px;background: #181818;border-radius: 12px;}
	.job-delivery-rider-main .job-personal-info .form-group {display: flex;flex-flow: row wrap;gap: 20px;}
	.job-delivery-rider-main .job-form-input {display: flex;background: transparent;border: 1px solid rgba(236, 78, 52, 0.5);border-radius: 50px;color: #fff;padding: 15px 20px;font-family: 'Montserrat';max-width: 100%;width: calc(50% - 10px);}
	.job-delivery-rider-main .job-form-input::placeholder{font-size: 16px;color:#fff;font-weight:200;font-family: 'Montserrat';letter-spacing: 1px;}
	.job-delivery-rider-main input:focus-visible,input:focus,textarea:focus,textarea:focus-visible  {outline: none;}
	.job-delivery-rider-main .job-personal-questions .form-group {padding: 30px 40px;background-color: #181818;margin-bottom: 20px;border-radius: 12px;}
	.job-delivery-rider-main .form-address-box{display: flex;flex-flow: row wrap;gap:16px;}
	.job-delivery-rider-main .job-apply-form-toggle{margin-left: 40px;}
	.job-delivery-rider-main p.job-apply-form-question {margin-bottom: 16px;font-weight: 500;line-height: 30px;display: flex;}
	.job-delivery-rider-main p.job-apply-form-question span{margin-right: 10px;font-weight: 600;}
	.job-delivery-rider-main .job-apply-form-toggle {position: relative;display: inline-block;width: 70px;height: 32px;padding: 5px;}
	.job-delivery-rider-main .job-apply-form-toggle input {display: none;}
	.job-delivery-rider-main  .job-form-control {display: flex;flex-direction: column;gap: 10px;width: calc(50% - 10px);}
	.job-delivery-rider-main  .job-form-control .job-form-input{display: flex;flex-direction: column;gap: 10px;width: 100%;}	
	.job-delivery-rider-main .job-apply-form-toggle .slider {position: absolute;cursor: pointer;top: 0;left: 0;right: 0;bottom: 0;background-color: #95A5A6;transition: .4s;border-radius: 32px;}
	.job-delivery-rider-main .job-apply-form-toggle .slider:before {position: absolute;content: "";height: 20px;width: 20px;left: 5px;bottom: 6px;background-color: #fff;transition: .4s;border-radius: 50%;}
	.job-delivery-rider-main .job-apply-form-toggle .yes,.job-apply-form-toggle .no {position: absolute;width: 100%;text-align: center;line-height: 32px;font-size: 12px;color: #fff;transition: opacity .4s;color: #fff;font-family: 'Montserrat';font-size: 16px;font-weight: 500;text-transform: uppercase;}
	.job-delivery-rider-main .job-apply-form-toggle .yes {opacity: 0;left: 10px;width: fit-content;}
	.job-delivery-rider-main .job-apply-form-toggle .no {opacity: 1;left: 45%;width: fit-content;opacity: 50%;}
	.job-delivery-rider-main .job-apply-form-toggle input:checked + .slider {background-color: #EC4E34;}
	.job-delivery-rider-main .job-apply-form-toggle input:checked + .slider:before {transform: translateX(38px);}
	.job-delivery-rider-main .job-apply-form-toggle input:checked + .slider .yes {opacity: 1;}
	.job-delivery-rider-main .job-apply-form-toggle input:checked + .slider .no {opacity: 0;}	
	.job-delivery-rider-main button.job-apply-form-submit { width: fit-content ;padding: 19px 80px ;border: 1px solid  #EC4E34;background-color: #EC4E34;display: flex ;align-items: center;gap: 12px;border-radius: 50px ;font-size: 18px ;font-weight: 600 ;font-family: 'Montserrat' ;color: #fff ;text-decoration: none ;transition: all 0.3s ease-in-out ;margin-top:50px;}
	.job-delivery-rider-main button.job-apply-form-submit>* { transition: all 0.3s ease-in-out;}
	.job-delivery-rider-main button.job-apply-form-submit span, button.job-apply-form-submit svg {transform: translateX(0);}
	.job-delivery-rider-main .supavapes-link-section a.supavapes-link:hover,.job-delivery-rider-main a.sv-form-loaction-link:hover,.job-delivery-rider-main button.job-apply-form-submit:hover {background-color: transparent ;}
	.job-delivery-rider-main button.job-apply-form-submit svg * {fill: #fff;}
	.job-delivery-rider-main .job-personal-questions .form-group .job-form-control {margin-left: 40px;}
	.job-delivery-rider-main .job-form-control input#message {margin: 0;}
	.job-delivery-rider-main a.sv-form-loaction-link {width: fit-content;padding: 10px 30px;height:48px;border: 1px solid #EC4E34;background-color: #EC4E34;display: flex;align-items: center;gap: 12px;border-radius: 50px;font-size: 14px;font-weight: 500;font-family: 'Montserrat';color: #fff;text-decoration: none;transition: all 0.3s ease-in-out;}
	@media (max-width:1200px) {
		.job-delivery-rider-main .job-banner-details {gap: 30px;}
		.job-delivery-rider-main .job-banner-left, .job-banner-right {max-width: calc(50% - 15px);}
		.job-delivery-rider-main p.job-banner-text {margin: 0 0 20px;}	
		.job-delivery-rider-main .requirements-ighlights-details {gap: 30px;}
		.job-delivery-rider-main .requirements-ighlights-box ul {padding: 20px;gap: 15px;}
		.job-delivery-rider-main h3.requirements-ighlights-box-title {padding: 15px 20px;}		
	}
	@media (max-width:992px) {
		.job-delivery-rider-main .job-personal-questions .form-group input {margin: 0;width: 100%;}	
		.job-delivery-rider-main .form-address-box {padding-left: 40px;}
		.job-delivery-rider-main .form-address-box .job-form-control{padding-left: 0 !important;}
		.job-delivery-rider-main .job-personal-questions .form-group .job-form-control{padding-left:40px;margin:0; width: 100%;}
		.job-delivery-rider-main .job-personal-questions .form-group textarea{margin:0;}
	}
	@media (max-width:768px) {
		.job-delivery-rider-main .job-banner-left {max-width: 100%;}
		.job-delivery-rider-main .job-banner-right { display: none;}
		.job-delivery-rider-main .job-banner-mobile-img {display:flex;align-items:center;justify-content: center;margin-bottom:30px}
		.job-delivery-rider-main h1.job-banner-main-title{margin-bottom:20px}
		.job-delivery-rider-main .job-banner-subtitle, .job-banner-title, .job-banner-main-title {text-align: center;}
		.job-delivery-rider-main p.job-banner-text {text-align: center;}
		.job-delivery-rider-main a.job-banner-button,.job-delivery-rider-main .share-banner-button{ padding: 12px 25px;}
		.job-banner-buttons {
			justify-content: center;
		}
		.share-option {
			justify-content: center;
		}
		.job-banner-share {
			align-items: center;
			width: 100%;
		}
		.job-delivery-rider-main .job-banner-subtitle {font-size: 18px;}
		.job-delivery-rider-main .supavapes-link-section a.supavapes-link img {width: 18px;}
		.job-delivery-rider-main .requirements-ighlights-details {flex-direction: column;}		
		.job-delivery-rider-main .requirements-ighlights-box ul {padding: 15px;}
		.job-delivery-rider-main .requirements-ighlights-box ul li {gap: 10px;}
		.job-delivery-rider-main h3.requirements-ighlights-box-title {padding: 15px 15px;}
		.job-delivery-rider-main .requirements-ighlights-box ul li span {width: calc(100% - 29px);font-size:16px;}
		.job-delivery-rider-main .supavapes-link-section a.supavapes-link {padding: 10px 20px;gap: 10px;border-radius: 50px;font-size: 16px;font-weight: 400;}
		.job-delivery-rider-main .job-personal-info {padding: 20;}
		.job-delivery-rider-main .job-personal-questions .form-group {padding: 20px;}
		.job-delivery-rider-main .job-form-input {width: 100%;}
		.job-delivery-rider-main .job-apply-form-section p {font-size: 16px;line-height: 24px;} 
		.job-delivery-rider-main .job-apply-form-toggle {margin-left: 32px;}
		.job-delivery-rider-main .form-address-box {padding-left: 32px;}
		.job-delivery-rider-main button.job-apply-form-submit {padding: 15px 30px;border-radius: 50px ;font-size: 16px ;font-weight: 500 ;margin-top:30px;}
		.job-delivery-rider-main .job-personal-questions .form-group .job-form-control{padding-left:32px}
		.job-delivery-rider-main .job-form-control { width: 100%;}
		.job-delivery-rider-main button.job-apply-form-submit svg {width: 18px;}
	}
	@media (max-width:575px) {
		.job-delivery-rider-main p.job-apply-form-question {display: block;}
		.job-delivery-rider-main p.job-apply-form-question span {margin-right: 5px;}
		.job-delivery-rider-main .job-personal-questions .form-group textarea,.job-delivery-rider-main .job-apply-form-toggle {margin-left: 0px;}
		.job-delivery-rider-main .form-address-box {padding-left: 0px;}
		.job-delivery-rider-main h3.job-apply-form-title {margin: 0 0 15px;}
		.job-delivery-rider-main .job-personal-questions .form-group .job-form-control{padding-left:0px}
		.job-delivery-rider-main .job-form-input::placeholder{font-size: 14px;}
		.job-delivery-rider-main a.sv-form-loaction-link {padding: 10px 20px;}
	}
	
</style>

<main class="job-delivery-rider-main">
<div class="supavapes-link-section">
		<div class="job-container">
			<a class="supavapes-link" href="https://www.supavapes.com/" target="_blank">
				<img src="/wp-content/uploads/2024/06/Supa-Vapes-Logo.png">Goto SupaVapes.com
			</a>
		</div>
	</div>
	<section class="job-banner">
		<div class="job-container">
			<div class="job-banner-details">
				<div class="job-banner-left">
					<h3 class="job-banner-subtitle">NOW</h3>
					<h2 class="job-banner-title">Part-Time</h2>
					<h1 class="job-banner-main-title"><span>Delivery</span> Driver</h1>
					<div class="job-banner-mobile-img">
						<img src="/wp-content/uploads/2024/06/job-banner.png">
					</div>
					<p class="job-banner-text">Be part of a team that wants to provide the best customer service. In this role, you will deliver 
					customer orders directly to them. Your route will start at 1 p.m., and you can expect to deliver 7-20 packages a day across our wide service area.</p>
					<div class="job-banner-buttons">
						<a class="job-banner-button" href="#job-apply-form-section">Apply Now</a>
						<div class="job-banner-share">
							<button type="button" class="share-banner-button share-btn">share Now 
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M17.1685 6.83144L9.2485 12.3303L0.964495 9.56858C0.386257 9.37544 -0.00330583 8.83293 2.11451e-05 8.22343C0.0033919 7.61394 0.39742 7.07475 0.97789 6.88835L22.1573 0.0678196C22.6607 -0.0940205 23.2133 0.038796 23.5872 0.412775C23.9612 0.786754 24.094 1.3393 23.9322 1.84276L17.1116 23.0221C16.9252 23.6026 16.386 23.9966 15.7766 24C15.1671 24.0033 14.6245 23.6137 14.4314 23.0355L11.6563 14.7114L17.1685 6.83144Z" fill="#EC4E34"></path>
						</svg></button>
							<div class="share-option">
								<a href="https://www.facebook.com/share/sharer.php?u=https://www.supavapes.com/pages/part-time-delivery-driver" class="share-icon" target="_blank">
									<svg width="23" height="41" viewBox="0 0 23 41" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M6.58856 23.3108H1.63237C0.831368 23.3108 0.581055 23.0104 0.581055 22.2595V16.2019C0.581055 15.4009 0.88143 15.1506 1.63237 15.1506H6.58856V10.7451C6.58856 8.74256 6.939 6.84018 7.94025 5.08799C8.99157 3.28573 10.4934 2.08423 12.3958 1.38336C13.6474 0.932794 14.899 0.732544 16.2506 0.732544H21.1568C21.8576 0.732544 22.158 1.03292 22.158 1.7338V7.44093C22.158 8.14181 21.8576 8.44218 21.1568 8.44218C19.8051 8.44218 18.4534 8.44218 17.1017 8.49224C15.75 8.49224 15.0491 9.14306 15.0491 10.5448C14.9991 12.0467 15.0491 13.4985 15.0491 15.0504H20.8564C21.6574 15.0504 21.9578 15.3508 21.9578 16.1518V22.2094C21.9578 23.0104 21.7075 23.2607 20.8564 23.2607H15.0491V39.5811C15.0491 40.4322 14.7988 40.7325 13.8977 40.7325H7.63988C6.88894 40.7325 6.58856 40.4322 6.58856 39.6812V23.3108Z" fill="#EC4E34"/>
									</svg>
								</a>
								<a href="https://www.linkedin.com/share?url=https://www.supavapes.com/pages/part-time-delivery-driver" class="share-icon" target="_blank">
									<svg width="41" height="41" viewBox="0 0 41 41" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M40.1689 40.7325V26.0825C40.1689 18.8825 38.6189 13.3825 30.2189 13.3825C26.1689 13.3825 23.4689 15.5825 22.3689 17.6825H22.2689V14.0325H14.3189V40.7325H22.6189V27.4825C22.6189 23.9825 23.2689 20.6325 27.5689 20.6325C31.8189 20.6325 31.8689 24.5825 31.8689 27.6825V40.6825H40.1689V40.7325ZM0.818945 14.0325H9.11895V40.7325H0.818945V14.0325ZM4.96895 0.732544C2.31895 0.732544 0.168945 2.88254 0.168945 5.53254C0.168945 8.18254 2.31895 10.3825 4.96895 10.3825C7.61895 10.3825 9.76895 8.18254 9.76895 5.53254C9.76895 2.88254 7.61895 0.732544 4.96895 0.732544Z" fill="#EC4E34"/>
									</svg>
								</a>
								<a href="https://api.whatsapp.com/send?text=https://www.supavapes.com/pages/part-time-delivery-driver" class="share-icon" target="_blank">
									<svg width="41" height="41" viewBox="0 0 41 41" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M20.71 0.732544C9.65996 0.732544 0.709961 9.68254 0.709961 20.7325C0.709961 25.1325 2.10996 29.1825 4.50996 32.4825L1.75996 39.7325L9.70996 37.4325C12.86 39.5325 16.66 40.7325 20.71 40.7325C31.76 40.7325 40.71 31.7825 40.71 20.7325C40.71 9.68254 31.76 0.732544 20.71 0.732544ZM31.36 29.0325L29.21 31.1325C26.96 33.3825 21.01 30.9325 15.76 25.6325C10.51 20.3825 8.15996 14.4325 10.26 12.2325L12.41 10.0825C13.21 9.28254 14.56 9.28254 15.41 10.0825L18.56 13.2325C19.66 14.3325 19.21 16.2325 17.76 16.6825C16.76 17.0325 16.06 18.1325 16.41 19.1325C16.96 21.4825 20.01 24.3825 22.26 24.9325C23.26 25.1825 24.41 24.5825 24.71 23.5825C25.16 22.1325 27.06 21.6825 28.16 22.7825L31.31 25.9325C32.11 26.7825 32.11 28.1325 31.36 29.0325Z" fill="#EC4E34"/>
									</svg>
								</a>
								<a href="mailto:?subject=Part Time Delivery Driver&body=https://www.supavapes.com/pages/part-time-delivery-driver" class="share-icon">
									<svg width="41" height="28" viewBox="0 0 41 28" fill="none" xmlns="http://www.w3.org/2000/svg" target="_blank">
									<path d="M23.958 14.545L38.4502 0.443481C38.1221 0.310669 37.7705 0.240356 37.4111 0.240356H2.87988C2.52832 0.240356 2.16895 0.310669 1.84082 0.443481L16.3252 14.545C18.4502 16.6154 21.8408 16.6154 23.958 14.545Z" fill="#EC4E34"/>
									<path d="M15.833 16.0294L0.59082 1.48254C0.301758 1.92786 0.145508 2.44348 0.145508 2.97473V24.4904C0.145508 25.9982 1.37207 27.2247 2.87988 27.2247H37.4111C38.9189 27.2247 40.1455 25.9982 40.1455 24.4904V2.97473C40.1455 2.44348 39.9893 1.92786 39.7002 1.48254L24.458 16.0294C22.0439 18.3341 18.2471 18.3341 15.833 16.0294Z" fill="#EC4E34"/>
									</svg>
								</a>
								<a href="https://twitter.com/share?url=https://www.supavapes.com/pages/part-time-delivery-driver" class="share-icon" target="_blank">
									<svg width="40" height="41" viewBox="0 0 40 41" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M23.8863 17.6698L38.457 0.732544H35.0042L22.3525 15.4389L12.2476 0.732544H0.592773L15.8734 22.9712L0.592773 40.7325H4.04576L17.4063 25.2021L28.0779 40.7325H39.7327L23.8855 17.6698H23.8863ZM19.157 23.1671L17.6087 20.9527L5.28992 3.33189H10.5935L20.5349 17.5524L22.0832 19.7669L35.0059 38.2514H29.7023L19.157 23.168V23.1671Z" fill="#EC4E34"/>
									</svg>
								</a>						
							</div>
						</div>
					</div>
					
				</div>
				<div class="job-banner-right">
					<img src="/wp-content/uploads/2024/06/job-banner.png">
				</div>
			</div>
		</div>
	</section>
	<section class="job-requirements-ighlights">
		<div class="job-container">
		<h3 class="requirements-description-title">Job Description:</h3>
					<ul class="requirements-description-list">
						<li>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.6272 8.74008L0.913266 1.03603C0.64713 0.915338 0.329566 0.987242 0.144427 1.2158C0.0538343 1.3265 0.00307248 1.46438 0.000275068 1.60732C-0.00252234 1.75027 0.0428066 1.89002 0.128999 2.00418L5.6253 9.32302L0.128999 16.6419C-0.0484251 16.8768 -0.0419967 17.203 0.143142 17.4302C0.267853 17.5856 0.454277 17.6691 0.643273 17.6691C0.734556 17.6691 0.82584 17.6498 0.91198 17.61L17.6259 9.90596C17.8547 9.80067 18 9.5734 18 9.32302C18 9.07264 17.8547 8.84537 17.6272 8.74008Z" fill="#EC4E34"/>
							</svg>
							<span>Deliver customer orders directly to their doorsteps</span>
						</li>
						<li>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.6272 8.74008L0.913266 1.03603C0.64713 0.915338 0.329566 0.987242 0.144427 1.2158C0.0538343 1.3265 0.00307248 1.46438 0.000275068 1.60732C-0.00252234 1.75027 0.0428066 1.89002 0.128999 2.00418L5.6253 9.32302L0.128999 16.6419C-0.0484251 16.8768 -0.0419967 17.203 0.143142 17.4302C0.267853 17.5856 0.454277 17.6691 0.643273 17.6691C0.734556 17.6691 0.82584 17.6498 0.91198 17.61L17.6259 9.90596C17.8547 9.80067 18 9.5734 18 9.32302C18 9.07264 17.8547 8.84537 17.6272 8.74008Z" fill="#EC4E34"/>
							</svg>
							<span>Typical shift starts at 1 PM, with 7-20 packages per day across our service area</span>
						</li>
					</ul>
			<div class="requirements-ighlights-details">

			
				<div class="requirements-ighlights-box">
					<h3 class="requirements-ighlights-box-title">Requirements:</h3>
					<ul>
						<li>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.6272 8.74008L0.913266 1.03603C0.64713 0.915338 0.329566 0.987242 0.144427 1.2158C0.0538343 1.3265 0.00307248 1.46438 0.000275068 1.60732C-0.00252234 1.75027 0.0428066 1.89002 0.128999 2.00418L5.6253 9.32302L0.128999 16.6419C-0.0484251 16.8768 -0.0419967 17.203 0.143142 17.4302C0.267853 17.5856 0.454277 17.6691 0.643273 17.6691C0.734556 17.6691 0.82584 17.6498 0.91198 17.61L17.6259 9.90596C17.8547 9.80067 18 9.5734 18 9.32302C18 9.07264 17.8547 8.84537 17.6272 8.74008Z" fill="#EC4E34"/>
							</svg>
							<span>Cell phone to sync with delivery software</span>
						</li>
						<li>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.6272 8.74008L0.913266 1.03603C0.64713 0.915338 0.329566 0.987242 0.144427 1.2158C0.0538343 1.3265 0.00307248 1.46438 0.000275068 1.60732C-0.00252234 1.75027 0.0428066 1.89002 0.128999 2.00418L5.6253 9.32302L0.128999 16.6419C-0.0484251 16.8768 -0.0419967 17.203 0.143142 17.4302C0.267853 17.5856 0.454277 17.6691 0.643273 17.6691C0.734556 17.6691 0.82584 17.6498 0.91198 17.61L17.6259 9.90596C17.8547 9.80067 18 9.5734 18 9.32302C18 9.07264 17.8547 8.84537 17.6272 8.74008Z" fill="#EC4E34"/>
							</svg>
							<span>Familiarity with GPS navigation</span>
						</li>
						<li>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.6272 8.74008L0.913266 1.03603C0.64713 0.915338 0.329566 0.987242 0.144427 1.2158C0.0538343 1.3265 0.00307248 1.46438 0.000275068 1.60732C-0.00252234 1.75027 0.0428066 1.89002 0.128999 2.00418L5.6253 9.32302L0.128999 16.6419C-0.0484251 16.8768 -0.0419967 17.203 0.143142 17.4302C0.267853 17.5856 0.454277 17.6691 0.643273 17.6691C0.734556 17.6691 0.82584 17.6498 0.91198 17.61L17.6259 9.90596C17.8547 9.80067 18 9.5734 18 9.32302C18 9.07264 17.8547 8.84537 17.6272 8.74008Z" fill="#EC4E34"/>
							</svg>
							<span>Registration with QuickBooks Workforce and Time Tracking</span>
						</li>
						<li>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.6272 8.74008L0.913266 1.03603C0.64713 0.915338 0.329566 0.987242 0.144427 1.2158C0.0538343 1.3265 0.00307248 1.46438 0.000275068 1.60732C-0.00252234 1.75027 0.0428066 1.89002 0.128999 2.00418L5.6253 9.32302L0.128999 16.6419C-0.0484251 16.8768 -0.0419967 17.203 0.143142 17.4302C0.267853 17.5856 0.454277 17.6691 0.643273 17.6691C0.734556 17.6691 0.82584 17.6498 0.91198 17.61L17.6259 9.90596C17.8547 9.80067 18 9.5734 18 9.32302C18 9.07264 17.8547 8.84537 17.6272 8.74008Z" fill="#EC4E34"/>
							</svg>
							<span>Commitment to kindness and courtesy with customers</span>
						</li>	
					</ul>
				</div>
				<div class="requirements-ighlights-box">
					<h3 class="requirements-ighlights-box-title">Highlights:</h3>
					<ul>
						<li>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.6272 8.74008L0.913266 1.03603C0.64713 0.915338 0.329566 0.987242 0.144427 1.2158C0.0538343 1.3265 0.00307248 1.46438 0.000275068 1.60732C-0.00252234 1.75027 0.0428066 1.89002 0.128999 2.00418L5.6253 9.32302L0.128999 16.6419C-0.0484251 16.8768 -0.0419967 17.203 0.143142 17.4302C0.267853 17.5856 0.454277 17.6691 0.643273 17.6691C0.734556 17.6691 0.82584 17.6498 0.91198 17.61L17.6259 9.90596C17.8547 9.80067 18 9.5734 18 9.32302C18 9.07264 17.8547 8.84537 17.6272 8.74008Z" fill="#EC4E34"/>
							</svg>
							<span>Competitive pay starting at $21/hour</span>
						</li>
						<li>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.6272 8.74008L0.913266 1.03603C0.64713 0.915338 0.329566 0.987242 0.144427 1.2158C0.0538343 1.3265 0.00307248 1.46438 0.000275068 1.60732C-0.00252234 1.75027 0.0428066 1.89002 0.128999 2.00418L5.6253 9.32302L0.128999 16.6419C-0.0484251 16.8768 -0.0419967 17.203 0.143142 17.4302C0.267853 17.5856 0.454277 17.6691 0.643273 17.6691C0.734556 17.6691 0.82584 17.6498 0.91198 17.61L17.6259 9.90596C17.8547 9.80067 18 9.5734 18 9.32302C18 9.07264 17.8547 8.84537 17.6272 8.74008Z" fill="#EC4E34"/>
							</svg>
							<span>Pay increase to $22/hour after 3 months</span>
						</li>
						<li>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M17.6272 8.74008L0.913266 1.03603C0.64713 0.915338 0.329566 0.987242 0.144427 1.2158C0.0538343 1.3265 0.00307248 1.46438 0.000275068 1.60732C-0.00252234 1.75027 0.0428066 1.89002 0.128999 2.00418L5.6253 9.32302L0.128999 16.6419C-0.0484251 16.8768 -0.0419967 17.203 0.143142 17.4302C0.267853 17.5856 0.454277 17.6691 0.643273 17.6691C0.734556 17.6691 0.82584 17.6498 0.91198 17.61L17.6259 9.90596C17.8547 9.80067 18 9.5734 18 9.32302C18 9.07264 17.8547 8.84537 17.6272 8.74008Z" fill="#EC4E34"/>
							</svg>
							<span>Further increase to $24/hour after 6 monthsv</span>
						</li>	
					</ul>
				</div>
			</div>
		</div>
	</section>
	<section class="job-apply-form-section" id="job-apply-form-section">
		<div class="job-container">
			<h4 class="please-note">Please note:</h4>
			<p>Positions are limited, and we will stop hiring once all roles are filled. Apply today to secure your spot!</p>
			<form class="job-apply-form" id="job-apply-form" method="post" action="https://woocommerce-401163-4488997.cloudwaysapps.com/part-time-driver-job-form-submission.php">
				<div class="job-personal-info">
					<h3 class="job-apply-form-title">Your Personal Details</h3>
					<div class="form-group">
						<div class="job-form-control">
							<input type="text" placeholder="First name" name="first-name" class="job-form-input" id="first-name" required>
							<span class="error-message" id="first-name-error"></span>
						</div>	
						<div class="job-form-control">
							<input type="text" placeholder="Last name" name="last-name" class="job-form-input" id="last-name" required>
							<span class="error-message" id="last-name-error"></span>
						</div>	
						<div class="job-form-control">
							<input type="email" placeholder="Email address" name="Email" class="job-form-input" id="email-address" required>
							<span class="error-message" id="email-error"></span>
						</div>
						<div class="job-form-control">
							<input type="phone" placeholder="Phone number" name="Phone-number" class="job-form-input" id="phone-number" required>
							<span class="error-message" id="phone-error"></span>
						</div>		
					</div>							
				</div>
				<div class="job-personal-questions">
					<h3 class="job-apply-form-title">Before proceeding with a quick interview, we have the following questions:</h3>
					<div class="form-group">
						<p class="job-apply-form-question"><span>Q1.</span>Are you comfortable driving to diverse locations during the afternoon to evening hours?</p>
						<label class="job-apply-form-toggle" name="question1">
						<input type="checkbox" id="question1" name="question1">

							<!-- <input type="checkbox" name="question1"> -->
							<span class="slider">
								<span class="yes">Yes</span>
								<span class="no">No</span>
							</span>
						</label>
					</div>	
					<div class="form-group">
						<p class="job-apply-form-question"><span>Q2.</span>Are you comfortable driving to diverse locations during the afternoon to evening hours?</p>
						<label class="job-apply-form-toggle">
						<input type="checkbox" id="question2" name="question2">
							<span class="slider">
								<span class="yes">Yes</span>
								<span class="no">No</span>
							</span>
						</label>
					</div>	
					<div class="form-group">
						<p class="job-apply-form-question"><span>Q3.</span>Are you comfortable driving to diverse locations during the afternoon to evening hours?</p>
						<label class="job-apply-form-toggle">
						<input type="checkbox" id="question3" name="question3">
							<span class="slider">
								<span class="yes">Yes</span>
								<span class="no">No</span>
							</span>
						</label>
					</div>
					<div class="form-group">
						<p class="job-apply-form-question"><span>Q4.</span>Help us with your location.</p>
						<div class="form-address-box">	
						<div class="job-form-control">					
							<input type="text" placeholder="Enter your location" name="location" class="job-form-input" id="location" required>
							<span class="error-message" id="location-error"></span>
							</div>	
							<a href="javascript:void(0);" class="sv-form-loaction-link" id="locate-button">
							<svg width="12" height="18" viewBox="0 0 12 18" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M6.00001 0C2.69167 0 0 2.69167 0 6.00001C0 6.99318 0.248309 7.9779 0.720351 8.85132L5.6719 17.8066C5.73782 17.926 5.86343 18 6.00001 18C6.13659 18 6.26221 17.926 6.32812 17.8066L11.2815 8.84837C11.7517 7.9779 12 6.99314 12 5.99998C12 2.69167 9.30836 0 6.00001 0ZM6.00001 9C4.34584 9 3.00002 7.65418 3.00002 6.00001C3.00002 4.34584 4.34584 3.00002 6.00001 3.00002C7.65418 3.00002 9 4.34584 9 6.00001C9 7.65418 7.65418 9 6.00001 9Z" fill="white"></path>
							</svg>Locate my neighbourhood
							</a>
						</div>
					</div>	
					<div class="form-group">
						<p class="job-apply-form-question"><span>Q5.</span>If there is specific region you prefer for servicing, let us know.</p>
						<div class="job-form-control">
							<input type="text" placeholder="Enter your message" name="message" class="job-form-input" id="message" required>
							<span class="error-message" id="message-error"></span>
						</div>						
					</div>
					
					<button type="submit" name="submit" class="job-apply-form-submit" fdprocessedid="iqdyxj">
						<span>Submit</span>
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M17.1685 6.83144L9.2485 12.3303L0.964495 9.56858C0.386257 9.37544 -0.00330583 8.83293 2.11451e-05 8.22343C0.0033919 7.61394 0.39742 7.07475 0.97789 6.88835L22.1573 0.0678196C22.6607 -0.0940205 23.2133 0.038796 23.5872 0.412775C23.9612 0.786754 24.094 1.3393 23.9322 1.84276L17.1116 23.0221C16.9252 23.6026 16.386 23.9966 15.7766 24C15.1671 24.0033 14.6245 23.6137 14.4314 23.0355L11.6563 14.7114L17.1685 6.83144Z" fill="#EC4E34"></path>
						</svg>
					</button>
				</div>
			</form>
			<div id="success-message" class="success-message">Your application has been submitted successfully!</div>
		</div>
	</section>
</main>
<script>
	jQuery(document).ready(function () {
		
		
	

		jQuery(window).scroll(function() {
		if (jQuery(this).scrollTop() > 60) {
  
	jQuery('.supavapes-link-section').addClass('sticky');  
			} else {
	jQuery('.supavapes-link-section').removeClass('sticky');
			}
	});

	
		jQuery( ".share-btn" ).click(function(e) {
			jQuery(".share-option").toggleClass("active");

		});



	// jQuery('.error-message').hide();
	// Clear error messages on keyup
	jQuery('#first-name, #last-name, #email-address, #phone-number, #message').on('keyup', function() {
        jQuery(this).next('.error-message').text('');
    });

	// Show success message if success parameter is present in the URL
    if (window.location.search.indexOf('success=true') !== -1) {
		jQuery('#job-apply-form').hide();
        jQuery('#success-message').css('display','block');
		jQuery('html, body').scrollTop(jQuery('#success-message').offset().top - 200);


		
		
		// jQuery('.job-apply-form-submit').prop('disabled', true);
    }
	

	jQuery('.job-apply-form-submit').on('click', function(e) {
	// e.preventDefault(); // Prevent the form from submitting
	// Clear previous error messages
	jQuery('.error-message').text('');

	let valid = true;

	// Validate first name
	const firstName = jQuery('#first-name').val().trim();
	if (firstName === '') {
		jQuery('#first-name-error').text('First name is required.');
		valid = false;
	}

	// Validate last name
	const lastName = jQuery('#last-name').val().trim();
	if (lastName === '') {
		jQuery('#last-name-error').text('Last name is required.');
		valid = false;
	}
	// Validate Location
	const location = jQuery('#location').val().trim();
	if (location === '') {
		jQuery('#location-error').text('Location is required.');
		valid = false;
	}

	// Validate email address
	const email = jQuery('#email-address').val().trim();
	const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
	if (email === '') {
		jQuery('#email-error').text('Email address is required.');
		valid = false;
	} else if (!emailPattern.test(email)) {
		jQuery('#email-error').text('Please enter a valid email address.');
		valid = false;
	}

	// Validate phone number
	const phone = jQuery('#phone-number').val().trim();
	const phonePattern = /^[0-9]{10}$/; // Adjust the regex according to your requirements
	if (phone === '') {
		jQuery('#phone-error').text('Phone number is required.');
		valid = false;
	} else if (!phonePattern.test(phone)) {
		jQuery('#phone-error').text('Please enter a valid phone number.');
		valid = false;
	}

	// Validate message
	const message = jQuery('#message').val().trim();
	if (message === '') {
		jQuery('#message-error').text('Message is required.');
		valid = false;
	}

	// If the form is valid, you can proceed with form submission or other actions
	if (valid) {
		// alert('Form submitted successfully!');
		// You can submit the form here if you want
		jQuery('.job-apply-form').submit();
	}
});
jQuery('#locate-button').on('click', function(e) {
        // e.preventDefault(); // Prevent the default action of the link
		// alert('here');
		jQuery('#location-error').text('');
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                // Use the Google Maps Geocoding API to get location details
                jQuery.get('https://maps.googleapis.com/maps/api/geocode/json', {
                    latlng: lat + ',' + lng,
                    key: 'AIzaSyDRfDT-5iAbIjrIqVORmmeXwAjDgLJudiM'
                }, function(response) {
					// console.log(response);
                    if (response.status === 'OK') {
						// alert('in');
                        var result = response.results[0];
                        var city = '';
                        var country = '';

                        // Iterate through address components to find city and country
                        for (var i = 0; i < result.address_components.length; i++) {
                            var component = result.address_components[i];
                            if (component.types.includes('locality')) {
                                city = component.long_name;
                            }
                            if (component.types.includes('country')) {
                                country = component.long_name;
                            }
                        }

                        // Display the city and country in the location input field
                        jQuery('#location').val(city + ', ' + country);
                    } else {
                        jQuery('#location-error').text('Unable to retrieve your location. Please try again.');
                        jQuery('#location-error').show();
                    }
                });
            }, function(error) {
                jQuery('#location-error').text('Geolocation is not supported by this browser or permission denied.');
                jQuery('#location-error').show();
            });
        } else {
            jQuery('#location-error').text('Geolocation is not supported by this browser.');
            jQuery('#location-error').show();
        }
    });
});
</script>