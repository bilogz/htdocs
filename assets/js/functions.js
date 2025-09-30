let slideIndex = 0;
        const slides = document.querySelectorAll('.announcement-slide');
        const dotsContainer = document.querySelector('.slider-dots');
        const innerSlides = document.querySelectorAll('.announcement-slide .inner-slider img');

        function createDots() {
            const numSlides = innerSlides.length;
            for (let i = 0; i < numSlides; i++) {
                const dot = document.createElement('span');
                dot.classList.add('dot');
                if (i === 0) {
                    dot.classList.add('active');
                }
                dot.addEventListener('click', () => {
                    slideIndex = i;
                    showSlide(slideIndex);
                });
                dotsContainer.appendChild(dot);
            }
        }
        createDots();

        const dots = document.querySelectorAll('.slider-dots .dot');

        function showSlide(index) {
            if (index < 0) {
                slideIndex = innerSlides.length - 1;
            } else if (index >= innerSlides.length) {
                slideIndex = 0;
            }

            innerSlides.forEach((slide) => {
                slide.style.transform = `translateX(-${slideIndex * 100}%)`;
            });

            dots.forEach((dot, i) => {
                dot.classList.remove('active');
            });
            dots[slideIndex].classList.add('active');
        }

        function nextSlide() {
            showSlide(++slideIndex);
        }

        function prevSlide() {
            showSlide(--slideIndex);
        }

        setInterval(nextSlide, 3000);

 

