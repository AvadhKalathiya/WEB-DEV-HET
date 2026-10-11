document.addEventListener("DOMContentLoaded", function () {
	// Add the welcome banner without changing the existing page markup.
	const notificationBanner = document.createElement("div");
	notificationBanner.className = "notification-banner";
	notificationBanner.innerHTML = '📢 Welcome to CHARUSAT Student Portal! <button class="notification-close" type="button" aria-label="Close notification">&times;</button>';
	document.body.insertBefore(notificationBanner, document.body.firstChild);

	notificationBanner.querySelector(".notification-close").addEventListener("click", function () {
		notificationBanner.remove();
	});

	// Expand and collapse FAQ answers.
	document.querySelectorAll(".faq-question").forEach(function (question) {
		question.addEventListener("click", function () {
			const faqItem = question.parentElement;
			const isOpen = faqItem.classList.toggle("open");
			question.setAttribute("aria-expanded", isOpen);
			question.querySelector("span").textContent = isOpen ? "−" : "+";
		});
	});

	// Control the announcement modal with the button, close button, backdrop and Escape.
	const announcementModal = document.querySelector("#announcement-modal");
	const announcementButton = document.querySelector("#announcement-button");
	const modalCloseButton = document.querySelector("#modal-close");

	function closeAnnouncementModal() {
		announcementModal.classList.remove("show");
		announcementModal.setAttribute("aria-hidden", "true");
	}

	announcementButton.addEventListener("click", function () {
		announcementModal.classList.add("show");
		announcementModal.setAttribute("aria-hidden", "false");
		modalCloseButton.focus();
	});
	modalCloseButton.addEventListener("click", closeAnnouncementModal);
	announcementModal.addEventListener("click", function (event) {
		if (event.target === announcementModal) closeAnnouncementModal();
	});
	document.addEventListener("keydown", function (event) {
		if (event.key === "Escape") closeAnnouncementModal();
	});

	// Move between slides and restart automatic rotation after manual navigation.
	const slidesContainer = document.querySelector(".slides");
	const slides = document.querySelectorAll(".slide");
	const dotsContainer = document.querySelector(".slider-dots");
	let currentSlide = 0;
	let slideTimer;

	function showSlide(slideNumber) {
		currentSlide = (slideNumber + slides.length) % slides.length;
		slidesContainer.style.transform = "translateX(-" + (currentSlide * 100) + "%)";
		slides.forEach(function (slide, index) {
			slide.classList.toggle("active-slide", index === currentSlide);
		});
		dotsContainer.querySelectorAll(".slider-dot").forEach(function (dot, index) {
			dot.classList.toggle("active-dot", index === currentSlide);
		});
	}

	slides.forEach(function (_, index) {
		const dot = document.createElement("button");
		dot.className = "slider-dot";
		dot.type = "button";
		dot.setAttribute("aria-label", "Show slide " + (index + 1));
		dot.addEventListener("click", function () {
			showSlide(index);
			restartSlideTimer();
		});
		dotsContainer.appendChild(dot);
	});

	function restartSlideTimer() {
		clearInterval(slideTimer);
		slideTimer = setInterval(function () { showSlide(currentSlide + 1); }, 5000);
	}

	document.querySelector(".previous-slide").addEventListener("click", function () {
		showSlide(currentSlide - 1);
		restartSlideTimer();
	});
	document.querySelector(".next-slide").addEventListener("click", function () {
		showSlide(currentSlide + 1);
		restartSlideTimer();
	});
	showSlide(0);
	restartSlideTimer();

	// Open and close the existing navigation on small screens.
	const menuToggle = document.querySelector("#menu-toggle");
	const header = document.querySelector("header");
	menuToggle.addEventListener("click", function () {
		const menuIsOpen = header.classList.toggle("menu-open");
		menuToggle.setAttribute("aria-expanded", menuIsOpen);
		menuToggle.setAttribute("aria-label", menuIsOpen ? "Close menu" : "Open menu");
	});
	document.querySelectorAll("nav a").forEach(function (link) {
		link.addEventListener("click", function () {
			header.classList.remove("menu-open");
			menuToggle.setAttribute("aria-expanded", "false");
		});
	});

	// Persist the selected light or dark theme.
	const themeToggle = document.querySelector("#theme-toggle");

	function updateThemeButton() {
		const darkModeEnabled = document.body.classList.contains("dark-mode");
		themeToggle.textContent = darkModeEnabled ? "☀️" : "🌙";
		themeToggle.setAttribute("aria-label", darkModeEnabled ? "Switch to light mode" : "Switch to dark mode");
	}

	if (localStorage.getItem("theme") === "dark") document.body.classList.add("dark-mode");
	updateThemeButton();
	themeToggle.addEventListener("click", function () {
		const darkModeEnabled = document.body.classList.toggle("dark-mode");
		localStorage.setItem("theme", darkModeEnabled ? "dark" : "light");
		updateThemeButton();
	});
});