/**
 * ReSymf CMS - Frontend JavaScript
 * Public-facing website enhancements
 */

// Import styles
import './styles/cms.css';

// Smooth Scrolling for Anchor Links
class SmoothScroll {
    constructor() {
        this.init();
    }

    init() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                const href = anchor.getAttribute('href');
                if (href !== '#' && href !== '') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });
    }
}

// Reading Progress Bar
class ReadingProgress {
    constructor() {
        this.init();
    }

    init() {
        const article = document.querySelector('.cms-page-body');
        if (!article) return;

        // Create progress bar
        const progressBar = document.createElement('div');
        progressBar.className = 'reading-progress';
        progressBar.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(to right, #0d6efd, #0dcaf0);
            z-index: 9999;
            transition: width 0.1s ease;
        `;
        document.body.appendChild(progressBar);

        // Update progress on scroll
        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;
            progressBar.style.width = scrollPercent + '%';
        });
    }
}

// Table of Contents Generator
class TableOfContents {
    constructor() {
        this.init();
    }

    init() {
        const article = document.querySelector('.cms-page-body');
        if (!article) return;

        const headings = article.querySelectorAll('h2, h3');
        if (headings.length < 3) return; // Only show TOC if there are 3+ headings

        // Create TOC container
        const toc = document.createElement('div');
        toc.className = 'table-of-contents';
        toc.style.cssText = `
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        `;

        const tocTitle = document.createElement('h4');
        tocTitle.textContent = 'Table of Contents';
        tocTitle.style.marginTop = '0';
        toc.appendChild(tocTitle);

        const tocList = document.createElement('ul');
        tocList.style.cssText = 'list-style: none; padding-left: 0;';

        headings.forEach((heading, index) => {
            // Add ID to heading if it doesn't have one
            if (!heading.id) {
                heading.id = `section-${index}`;
            }

            const li = document.createElement('li');
            li.style.paddingLeft = heading.tagName === 'H3' ? '1rem' : '0';
            li.style.marginBottom = '0.5rem';

            const link = document.createElement('a');
            link.href = `#${heading.id}`;
            link.textContent = heading.textContent;
            link.style.cssText = 'text-decoration: none; color: #0d6efd;';

            li.appendChild(link);
            tocList.appendChild(li);
        });

        toc.appendChild(tocList);
        article.parentElement.insertBefore(toc, article);
    }
}

// Image Lightbox
class ImageLightbox {
    constructor() {
        this.init();
    }

    init() {
        const images = document.querySelectorAll('.cms-page-body img');
        images.forEach(img => {
            img.style.cursor = 'zoom-in';
            img.addEventListener('click', () => {
                this.openLightbox(img);
            });
        });
    }

    openLightbox(img) {
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            cursor: zoom-out;
        `;

        const lightboxImg = document.createElement('img');
        lightboxImg.src = img.src;
        lightboxImg.alt = img.alt;
        lightboxImg.style.cssText = `
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        `;

        overlay.appendChild(lightboxImg);
        document.body.appendChild(overlay);

        overlay.addEventListener('click', () => {
            overlay.remove();
        });

        // Close on ESC key
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                overlay.remove();
                document.removeEventListener('keydown', escHandler);
            }
        });
    }
}

// Code Syntax Highlighting (basic)
class CodeHighlighter {
    constructor() {
        this.init();
    }

    init() {
        const codeBlocks = document.querySelectorAll('.cms-page-body pre code');
        codeBlocks.forEach(block => {
            // Add line numbers
            const lines = block.textContent.split('\n');
            if (lines.length > 1) {
                block.style.counterReset = 'line';
                lines.forEach((line, index) => {
                    const lineDiv = document.createElement('div');
                    lineDiv.textContent = line;
                    lineDiv.style.cssText = `
                        counter-increment: line;
                        &::before {
                            content: counter(line);
                            margin-right: 1rem;
                            color: #999;
                        }
                    `;
                });
            }
        });
    }
}

// External Links Handler
class ExternalLinks {
    constructor() {
        this.init();
    }

    init() {
        const links = document.querySelectorAll('.cms-page-body a[href^="http"]');
        links.forEach(link => {
            const url = new URL(link.href);
            if (url.hostname !== window.location.hostname) {
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
                // Add external link icon
                link.innerHTML += ' <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor"><path d="M10.5 0h-9C.67 0 0 .67 0 1.5v9c0 .83.67 1.5 1.5 1.5h9c.83 0 1.5-.67 1.5-1.5v-9c0-.83-.67-1.5-1.5-1.5zM10 10H2V2h3v1H3v6h6V6h1v4z"/><path d="M6 1h5v5l-1.5-1.5L6 8 4 6l3.5-3.5z"/></svg>';
            }
        });
    }
}

// Print Friendly
class PrintHelper {
    constructor() {
        this.init();
    }

    init() {
        const printBtn = document.createElement('button');
        printBtn.className = 'btn btn-outline-secondary btn-sm';
        printBtn.innerHTML = '🖨 Print';
        printBtn.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 1000;';
        printBtn.addEventListener('click', () => {
            window.print();
        });

        const content = document.querySelector('.cms-content');
        if (content) {
            document.body.appendChild(printBtn);
        }
    }
}

// Estimated Reading Time
class ReadingTime {
    constructor() {
        this.init();
    }

    init() {
        const article = document.querySelector('.cms-page-body');
        if (!article) return;

        const text = article.textContent;
        const words = text.trim().split(/\s+/).length;
        const readingTime = Math.ceil(words / 200); // Average reading speed: 200 words/min

        const meta = document.querySelector('.cms-page-meta');
        if (meta) {
            const timeSpan = document.createElement('span');
            timeSpan.innerHTML = `<i>📖</i> ${readingTime} min read`;
            meta.appendChild(timeSpan);
        }
    }
}

// Back to Top Button
class BackToTop {
    constructor() {
        this.init();
    }

    init() {
        const btn = document.createElement('button');
        btn.className = 'back-to-top';
        btn.innerHTML = '↑';
        btn.style.cssText = `
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            z-index: 1000;
            font-size: 20px;
            transition: opacity 0.3s ease;
        `;

        btn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        document.body.appendChild(btn);

        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                btn.style.display = 'block';
            } else {
                btn.style.display = 'none';
            }
        });
    }
}

// Initialize all features when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SmoothScroll();
    new ReadingProgress();
    new TableOfContents();
    new ImageLightbox();
    new CodeHighlighter();
    new ExternalLinks();
    new PrintHelper();
    new ReadingTime();
    new BackToTop();

    console.log('✓ ReSymf CMS Frontend features initialized');
});

// Export for use in other modules
export {
    SmoothScroll,
    ReadingProgress,
    TableOfContents,
    ImageLightbox
};
