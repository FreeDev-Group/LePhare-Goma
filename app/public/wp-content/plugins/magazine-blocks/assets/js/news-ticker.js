!(function (t, e) {
	"use strict";
	"object" == typeof module &&
		"object" == typeof module.exports &&
		(module.exports = t.document
			? void 0
			: function (t) {
					if (!t.document)
						throw new Error(
							"jQuery requires a window with a document"
						);
			  });
})("undefined" != typeof window ? window : this),
	(function (t) {
		"use strict";

		// Modern News Ticker Implementation
		t.newsTickerInit = function (element, options = {}) {
			const ticker = t(element);
			if (!ticker.length) return;

			const settings = {
				effect: options.effect || "fade",
				direction: options.direction || "ltr",
				delayTimer: options.delayTimer || 4000,
				play: options.play !== false,
				scrollSpeed: options.scrollSpeed || 2,
				stopOnHover: options.stopOnHover !== false,
			};

			const items = ticker.find(".mzb-news-ticker-list li");
			const totalPosts = items.length;

			if (totalPosts === 0) return;

			let currentIndex = 0;
			let intervalId = null;
			let isPlaying = settings.play;
			let animationFrameId = null;
			let scrollPosition = 0;
			let originalWidth = 0;

			// Initialize ticker based on effect
			const initializeTicker = () => {
				// Ensure we have items to work with
				if (totalPosts === 0) return;
				
				// Mark as not initialized initially
				ticker.attr("data-initialized", "false");

				// First, hide ALL items completely
				items.each(function (idx) {
					const element = t(this);
					element.css({
						display: "none",
						position: "relative",
						left: "0",
						top: "0",
						opacity: "0",
						transform: "translateX(0)",
						transition: "none",
						visibility: "hidden",
						zIndex: "-1"
					});
				});

				if (settings.effect === "scroll") {
					// For scroll effect, show all items and set up scrolling
					items.css({
						display: "block",
						opacity: "1",
						visibility: "visible",
						zIndex: "1"
					});
					
					// Calculate total width for seamless scrolling
					const tickerList = ticker.find(".mzb-news-ticker-list");
					if (tickerList.length) {
						// Calculate original width of all items
						originalWidth = 0;
						items.each(function () {
							originalWidth += t(this).outerWidth(true) + 20; // Add spacing
						});
						
						// Only clone if we have enough content to scroll
						if (originalWidth > 0) {
							// Clone items for seamless infinite scroll
							items.each(function () {
								const clone = t(this).clone();
								clone.css("display", "block");
								tickerList.append(clone);
							});
						}
					}
					
					if (isPlaying) {
						startScrollAnimation();
					}
				} else {
					// For other effects, show only current item
					const currentItem = items.eq(currentIndex);
					currentItem.css({
						display: "block",
						opacity: "1",
						visibility: "visible",
						zIndex: "1"
					});
					
					if (isPlaying) {
						startAutoPlay();
					}
				}

				// Mark as initialized after setup
				ticker.attr("data-initialized", "true");
			};

			// Scroll animation for continuous scrolling
			const startScrollAnimation = () => {
				if (settings.effect !== "scroll" || !isPlaying) return;

				const tickerList = ticker.find(".mzb-news-ticker-list");
				if (!tickerList.length) return;

				const containerWidth = tickerList.parent().width() || 0;
				
				// Only scroll if we have enough content to scroll
				if (originalWidth <= containerWidth) return;
				
				const direction = settings.direction === "rtl" ? 1 : -1;

				// Calculate scroll speed based on scrollSpeed value (1-100)
				// Higher scrollSpeed = faster scrolling
				// Map scrollSpeed 1-100 to actual pixel speed 0.5-8 pixels per frame
				const minSpeed = 0.5;
				const maxSpeed = 8;
				const actualScrollSpeed = minSpeed + (settings.scrollSpeed - 1) * (maxSpeed - minSpeed) / 99;

				const animate = () => {
					if (!isPlaying || settings.effect !== "scroll") return;

					scrollPosition += actualScrollSpeed * direction;

					// Reset position for seamless infinite scroll
					if (direction === -1 && scrollPosition <= -originalWidth) {
						scrollPosition = 0;
					} else if (
						direction === 1 &&
						scrollPosition >= originalWidth
					) {
						scrollPosition = 0;
					}

					tickerList.css(
						"transform",
						`translateX(${scrollPosition}px)`
					);

					if (isPlaying) {
						animationFrameId = requestAnimationFrame(animate);
					}
				};

				animate();
			};

			// Auto play for fade and other effects
			const startAutoPlay = () => {
				if (settings.effect === "scroll") return;

				intervalId = setInterval(() => {
					if (isPlaying) {
						nextItem();
					}
				}, settings.delayTimer);
			};

			// Show next item with animation
			const nextItem = () => {
				if (settings.effect === "scroll") return;

				const currentItem = items.eq(currentIndex);
				currentIndex = (currentIndex + 1) % totalPosts;
				const nextItemElement = items.eq(currentIndex);

				animateTransition(currentItem, nextItemElement);
			};

			// Show previous item with animation
			const prevItem = () => {
				if (settings.effect === "scroll") return;

				const currentItem = items.eq(currentIndex);
				currentIndex =
					currentIndex === 0 ? totalPosts - 1 : currentIndex - 1;
				const prevItemElement = items.eq(currentIndex);

				animateTransition(currentItem, prevItemElement);
			};

			// Animate transition between items
			const animateTransition = (fromItem, toItem) => {
				// Calculate animation duration based on scrollSpeed (1-100)
				// Higher scrollSpeed = faster animation, lower = slower
				const baseDuration = 400; // Base duration in ms
				const speedMultiplier = Math.max(
					0.1,
					(100 - settings.scrollSpeed) / 100
				); // Invert so higher speed = faster
				const animationDuration = Math.round(
					baseDuration * speedMultiplier
				);

				// Set transition duration based on calculated speed
				const transitionDuration = `${animationDuration}ms`;
				fromItem.css(
					"transition",
					`opacity ${transitionDuration} ease-in-out, transform ${transitionDuration} ease-in-out`
				);
				toItem.css(
					"transition",
					`opacity ${transitionDuration} ease-in-out, transform ${transitionDuration} ease-in-out`
				);

				switch (settings.effect) {
					case "fade":
						fromItem.css("opacity", 0);
						setTimeout(() => {
							fromItem.css({
								display: "none",
								opacity: "0",
								transform: "translateX(0)",
								visibility: "hidden",
								zIndex: "-1"
							});
							toItem.css({
								display: "block",
								opacity: "0",
								visibility: "visible",
								zIndex: "1"
							});
							setTimeout(() => {
								toItem.css("opacity", 1);
							}, 50);
						}, animationDuration);
						break;

					case "slide-left":
						fromItem.css({
							opacity: 0,
							transform: "translateX(-100%)",
						});
						setTimeout(() => {
							fromItem.css({
								display: "none",
								transform: "translateX(0)",
								opacity: "0",
								visibility: "hidden",
								zIndex: "-1"
							});
							toItem.css({
								display: "block",
								transform: "translateX(100%)",
								opacity: "0",
								visibility: "visible",
								zIndex: "1"
							});
							setTimeout(() => {
								toItem.css({
									opacity: 1,
									transform: "translateX(0)",
								});
							}, 50);
						}, animationDuration);
						break;

					case "slide-right":
						fromItem.css({
							opacity: 0,
							transform: "translateX(100%)",
						});
						setTimeout(() => {
							fromItem.css({
								display: "none",
								transform: "translateX(0)",
								opacity: "0",
								visibility: "hidden",
								zIndex: "-1"
							});
							toItem.css({
								display: "block",
								transform: "translateX(-100%)",
								opacity: "0",
								visibility: "visible",
								zIndex: "1"
							});
							setTimeout(() => {
								toItem.css({
									opacity: 1,
									transform: "translateX(0)",
								});
							}, 50);
						}, animationDuration);
						break;

					case "none":
						fromItem.css({
							display: "none",
							visibility: "hidden",
							zIndex: "-1"
						});
						toItem.css({
							display: "block",
							visibility: "visible",
							zIndex: "1"
						});
						break;

					default:
						fromItem.css({
							display: "none",
							visibility: "hidden",
							zIndex: "-1"
						});
						toItem.css({
							display: "block",
							visibility: "visible",
							zIndex: "1"
						});
				}
			};

			// Handle navigation clicks
			ticker.on("click", ".mzb-news-ticker-nav-btn", function (e) {
				e.preventDefault();
				const action = t(this).data("action");

				if (action === "prev") {
					prevItem();
				} else if (action === "next") {
					nextItem();
				}
			});

			// Handle hover events for stop on hover
			if (settings.stopOnHover) {
				ticker.on("mouseenter", function () {
					isPlaying = false;
					if (intervalId) {
						clearInterval(intervalId);
						intervalId = null;
					}
					if (animationFrameId) {
						cancelAnimationFrame(animationFrameId);
						animationFrameId = null;
					}
				});

				ticker.on("mouseleave", function () {
					if (settings.play) {
						isPlaying = true;
						if (settings.effect === "scroll") {
							startScrollAnimation();
						} else {
							startAutoPlay();
						}
					}
				});
			}

			// Initialize the ticker with a slight delay to ensure DOM is ready
			setTimeout(() => {
				initializeTicker();
			}, 50);

			// Return API for external control
			return {
				play: () => {
					isPlaying = true;
					if (settings.effect === "scroll") {
						startScrollAnimation();
					} else {
						startAutoPlay();
					}
				},
				pause: () => {
					isPlaying = false;
					if (intervalId) {
						clearInterval(intervalId);
						intervalId = null;
					}
					if (animationFrameId) {
						cancelAnimationFrame(animationFrameId);
						animationFrameId = null;
					}
				},
				next: nextItem,
				prev: prevItem,
			};
		};

		// Initialize all news tickers on page load
		t(document).ready(function () {
			t(".mzb-news-ticker").each(function () {
				const ticker = t(this);

				// Get settings from data attributes
				const options = {
					effect: ticker.data("ticker-effect") || "scroll",
					direction: ticker.data("ticker-direction") || "ltr",
					delayTimer: ticker.data("delay-timer") || 4000,
					play: ticker.data("auto-play") !== false,
					scrollSpeed: ticker.data("scroll-speed") || 2,
					stopOnHover: ticker.data("stop-on-hover") !== false,
				};

				t.newsTickerInit(this, options);
			});
		});
	})(jQuery);
