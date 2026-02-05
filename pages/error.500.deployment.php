
<!DOCTYPE html>
<html lang="en">

<head>
  <base href="http://localhost:9000">
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" href="images/favicon.png">
  <title>Failed To Deploy APP</title>
  <style>
    .animate__animated {
      animation-duration: 1s;
      animation-fill-mode: both;
    }

    .animate__fadeIn {
      animation-name: fadeIn;
    }

    .animate__delay-1s {
      animation-delay: 1s;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    /* ------------------------- Osmo [https://osmo.supply/] ------------------------- */
    /* ------------------------- Variables ------------------------------------------- */
    :root {
      --color-light: var(--color-neutral-200);
      --color-dark: var(--color-neutral-800);
      --color-primary: #6f3ab9;
      --color-neutral-100: #ffffff;
      --color-neutral-200: #efeeec;
      --color-neutral-300: #121317;
      --color-neutral-400: transparent;
      --color-neutral-500: #818180;
      --color-neutral-600: #2c2c2c;
      --color-neutral-700: #1f1f1f;
      --color-neutral-800: #a8a8ab;
      --color-neutral-900: #000000;
      --color-white: var(--color-neutral-100);
      --color-black: var(--color-neutral-900);
      --color-error: var(--color-primary);
      --color-success: #0ba954;
      --cubic-default: cubic-bezier(0.65, 0.05, 0, 1);
      --duration-default: 0.735s;
      --animation-default: var(--duration-default) var(--cubic-default);
      --gap: 2em;
      --section-padding: calc(3.5em + (var(--gap) * 2));
      --container-padding: 2em;
      --header-height: calc(1.5em + (var(--gap) * 2));
      --footer-height: calc(2.785em + (var(--gap) * 2));
    }

    /* Tablet */
    @media screen and (max-width: 991px) {
      :root {
        --container-padding: 1.5em;
      }
    }

    /* Mobile Landscape */
    @media screen and (max-width: 767px) {
      :root {
        --container-padding: 1em;
        --section-padding: calc(var(--gap) * 2);
      }
    }

    /* Mobile Portrait */
    @media screen and (max-width: 479px) {
      :root {}
    }

    /* ------------------------- Scaling System by Osmo [https://osmo.supply/] -------------------------  */
    /* Desktop */
    :root {
      --size-unit: 16;
      /* body font-size in design - no px */
      --size-container-ideal: 1440;
      /* screen-size in design - no px */
      --size-container-min: 992px;
      --size-container-max: 1920px;
      --size-container: clamp(var(--size-container-min), 100vw, var(--size-container-max));
      --size-font: calc(var(--size-container) / (var(--size-container-ideal) / var(--size-unit)));
    }

    /* Tablet */
    @media screen and (max-width: 991px) {
      :root {
        --size-container-ideal: 834;
        /* screen-size in design - no px */
        --size-container-min: 768px;
        --size-container-max: 991px;
      }
    }

    /* Mobile Landscape */
    @media screen and (max-width: 767px) {
      :root {
        --size-container-ideal: 390;
        /* screen-size in design - no px */
        --size-container-min: 480px;
        --size-container-max: 767px;
      }
    }

    /* Mobile Portrait */
    @media screen and (max-width: 479px) {
      :root {
        --size-container-ideal: 440;
        /* screen-size in design - no px */
        --size-container-min: 0px;
        --size-container-max: 479px;
      }
    }

    /* ------------------------- Hide Scrollbar -------------------------------------------------- */

    body ::-webkit-scrollbar,
    body::-webkit-scrollbar {
      display: none;
    }

    /* Chrome, Safari, Opera */
    body {
      -ms-overflow-style: none;
    }

    /* IE & Edge */
    html {
      scrollbar-width: none;
    }

    /* Firefox */

    /* ------------------------- Reset -------------------------------------------------- */
    *,
    *:after,
    *:before {
      -webkit-box-sizing: border-box;
      -moz-box-sizing: border-box;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: initial;
    }

    html,
    body {
      -webkit-font-smoothing: antialiased;
    }

    svg {
      max-width: none;
      height: auto;
      box-sizing: border-box;
      vertical-align: middle;
    }

    a {
      color: inherit;
    }

    /* Selection */
    ::selection {
      background-color: var(--color-primary);
      color: var(--color-dark);
      text-shadow: none;
    }

    ::-moz-selection {
      background-color: var(--color-primary);
      color: var(--color-dark);
      text-shadow: none;
    }

    body {
      color: #333;
      font-family: 'PP Neue Montreal', Arial, Helvetica Neue, Helvetica, sans-serif;
      font-size: var(--size-font);
      line-height: 1;
      font-weight: 500;
    }

    h1 {
      margin-top: 0;
      margin-bottom: 0;
      font-size: 7.5em;
      font-weight: 500;
      line-height: 1;
    }

    h2 {
      margin-top: 0;
      margin-bottom: 0;
      font-size: 5em;
      font-weight: 500;
      line-height: 1.05;
    }

    h3 {
      margin-top: 0;
      margin-bottom: 0;
      font-size: 2.5em;
      font-weight: 500;
      line-height: 1.1;
    }

    h4 {
      margin-top: 10px;
      margin-bottom: 10px;
      font-size: 1.75em;
      font-weight: 500;
      line-height: 1.15;
    }

    h5 {
      margin-top: 10px;
      margin-bottom: 10px;
      font-size: 1.25em;
      font-weight: 500;
      line-height: 1.2;
    }

    p {
      margin-bottom: 0;
      margin-top: 0;
      font-size: 1em;
      font-weight: 400;
      line-height: 1.4;
      font-weight: 500;
    }

    a {
      color: inherit;
      text-decoration: underline;
    }

    strong {
      font-weight: 600;
    }

    .body {
      background-color: var(--color-neutral-300);
      color: var(--color-dark);
      font-family: PP Neue Montreal, Arial, sans-serif;
      font-weight: 500;
      line-height: 1;
    }

    .body.is--dark {
      background-color: var(--color-black);
      color: var(--color-light);
    }

    .code-embed-css {
      pointer-events: none;
      font-size: var(--size-font);
      width: 0;
      height: 0;
      position: absolute;
      top: 0;
      left: 0;
      overflow: hidden;
    }

    .code-embed-osmo,
    .code-embed-js {
      pointer-events: none;
      width: 0;
      height: 0;
      position: absolute;
      top: 0;
      left: 0;
      overflow: hidden;
    }

    .osmo-ui {
      z-index: 100;
      pointer-events: none;
      flex-flow: column;
      justify-content: space-between;
      align-items: stretch;
      display: flex;
      position: fixed;
      inset: 0;
    }

    .nav-row {
      justify-content: space-between;
      align-items: center;
      width: 100%;
      display: flex;
    }

    .nav-logo-row {
      pointer-events: auto;
      justify-content: space-between;
      align-items: center;
      width: 13em;
      display: flex;
    }

    .nav-logo__wordmark {
      width: 4em;
    }

    .nav-logo__icon {
      width: 1.5em;
      height: 1.5em;
    }

    .container {
      z-index: 1;
      max-width: var(--size-container);
      padding-left: var(--container-padding);
      padding-right: var(--container-padding);
      width: 100%;
      margin-left: auto;
      margin-right: auto;
      position: relative;
    }

    .container.is--full {
      max-width: 100%;
    }

    .container.is--medium {
      max-width: calc(var(--size-container) * .85);
    }

    .container.is--small {
      max-width: calc(var(--size-container) * .7);
    }

    .nav-row__right {
      grid-column-gap: .625rem;
      grid-row-gap: .625rem;
      pointer-events: auto;
      justify-content: flex-end;
      align-items: center;
      display: flex;
    }

    .header {
      padding-top: var(--gap);
      position: relative;
    }

    .website-link {
      white-space: nowrap;
      text-decoration: none;
      position: relative;
    }

    .website-link.is--alt {
      grid-column-gap: .25em;
      grid-row-gap: .25em;
      justify-content: flex-start;
      align-items: center;
      height: 1.5em;
      display: flex;
    }

    .inline-link__p {
      margin-bottom: 0;
    }

    .website-link__arrow-svg {
      width: 1em;
      margin-top: .2em;
    }

    .website-link__arrow-svg.is--duplicate {
      position: absolute;
      right: 100%;
    }

    .website-link__arrow {
      position: relative;
      overflow: hidden;
    }

    .cloneable {
      padding: var(--section-padding) var(--container-padding);
      justify-content: center;
      align-items: center;
      min-height: 100svh;
      display: flex;
      position: relative;
    }

    .footer {
      padding-bottom: var(--gap);
      position: relative;
    }

    .footer-row {
      justify-content: space-between;
      align-items: flex-end;
      display: flex;
    }

    .cloneable-title {
      grid-column-gap: .5em;
      grid-row-gap: .5em;
      pointer-events: auto;
      display: flex;
      position: relative;
    }

    .cloneable-title__nr {
      opacity: .5;
      white-space: nowrap;
      margin-bottom: 0;
      font-size: 1em;
      font-weight: 500;
      line-height: 1;
    }

    .cloneable-title__h1 {
      white-space: nowrap;
      margin-top: 0;
      margin-bottom: 0;
      font-size: 1em;
      font-weight: 500;
      line-height: 1;
    }

    .clone-in-webflow {
      grid-column-gap: .75em;
      grid-row-gap: .75em;
      background-color: var(--color-light);
      pointer-events: auto;
      border-radius: .25em;
      justify-content: space-between;
      align-items: center;
      width: 21.25em;
      height: 2.875em;
      margin-bottom: -1em;
      margin-right: -1em;
      padding-left: 1em;
      padding-right: .75em;
      text-decoration: none;
      display: flex;
    }

    .clone-in-webflow__p {
      margin-bottom: 0;
      font-size: 1em;
    }

    .webflow-logo-svg {
      flex-shrink: 0;
      width: 1.5em;
    }

    .cloneable-title__gradient {
      background-image: linear-gradient(270deg, var(--color-neutral-200), transparent);
      width: 1em;
      height: 100%;
      display: none;
      position: absolute;
      top: 0;
      right: 0;
    }

    .osmo-ui__bg {
      border-top-style: solid;
      border-top-width: 1px;
      border-top-color: var(--color-neutral-400);
      background-color: var(--color-neutral-300);
      height: calc(100% + 1px + (var(--gap) * .5));
      width: 100%;
      display: block;
      position: absolute;
      bottom: 0;
    }

    .osmo-ui__bg.is--header {
      border-top-style: none;
      border-bottom-style: solid;
      border-bottom-width: 1px;
      border-bottom-color: var(--color-neutral-400);
      height: calc(100% + 1px + var(--gap));
      top: 0;
      bottom: auto;
    }

    .osmo-icon-svg {
      width: 8em;
    }

    .styleguide {
      padding-bottom: calc(var(--footer-height) + var(--section-padding));
      padding-top: calc(var(--header-height) + var(--section-padding));
      flex-flow: column;
      justify-content: center;
      align-items: center;
      display: block;
    }

    .styleguide p {
      font-weight: 400;
    }

    .btn {
      background-color: var(--color-primary);
      color: var(--color-light);
      border-radius: .25em;
      flex: 0 auto;
      grid-template-rows: auto auto;
      grid-template-columns: 1fr 1fr;
      grid-auto-columns: 1fr;
      padding: .75em 1.5em;
      line-height: 1;
      text-decoration: none;
      display: inline-block;
    }

    .btn:hover {
      text-decoration: none;
    }

    .btn.is--secondary {
      background-color: var(--color-dark);
    }

    .btn-wrap {
      grid-column-gap: .5em;
      grid-row-gap: .5em;
      flex-wrap: wrap;
      align-items: flex-start;
      display: flex;
      position: relative;
    }

    .line {
      background-color: transparent;
      width: 100%;
      height: 1px;
      position: static;
    }

    .btn__text-p {
      margin-bottom: 0;
    }

    .styleguide__list {
      grid-column-gap: 2em;
      grid-row-gap: 2em;
      flex-flow: column;
      width: 100%;
      display: flex;
      position: relative;
    }

    @media screen and (max-width: 991px) {

      .container.is--medium,
      .container.is--small {
        max-width: calc(var(--size-container) * 1);
      }

      .clone-in-webflow {
        margin-right: -.5em;
      }
    }

    @media screen and (max-width: 767px) {
      h1 {
        font-size: 4em;
      }

      h2 {
        font-size: 3.25em;
      }

      .osmo-ui {
        position: fixed;
      }

      .nav-logo-row {
        grid-column-gap: 2.5em;
        grid-row-gap: 2.5em;
        width: auto;
      }

      .nav-row__right {
        grid-column-gap: 0rem;
        grid-row-gap: 0rem;
      }

      .cloneable-title {
        pointer-events: none;
        width: calc(100% - 5.25em);
        padding-left: 1em;
        position: absolute;
        overflow: hidden;
      }

      .cloneable-title__nr,
      .cloneable-title__h1 {
        font-size: .875em;
      }

      .clone-in-webflow {
        justify-content: flex-end;
        width: 100%;
        margin-right: 0;
        padding-left: .75em;
      }

      .clone-in-webflow__p {
        display: none;
      }

      .cloneable-title__gradient,
      .osmo-ui__bg {
        display: block;
      }
    }

    @font-face {
      font-family: 'PP Neue Montreal';
      src: url('https://cdn.prod.website-files.com/6756bf75aa4ecba10df0a4e9/6756bf75aa4ecba10df0a546_PPNeueMontreal-Regular.woff2') format('woff2');
      font-weight: 400;
      font-style: normal;
      font-display: swap;
    }

    @font-face {
      font-family: 'PP Neue Montreal';
      src: url('https://cdn.prod.website-files.com/6756bf75aa4ecba10df0a4e9/6756bf75aa4ecba10df0a54e_PPNeueMontreal-Medium.woff2') format('woff2');
      font-weight: 500;
      font-style: normal;
      font-display: swap;
    }

    @font-face {
      font-family: 'PP Neue Montreal';
      src: url('https://cdn.prod.website-files.com/6756bf75aa4ecba10df0a4e9/6756bf75aa4ecba10df0a543_PPNeueMontreal-SemiBold.woff2') format('woff2');
      font-weight: 600;
      font-style: normal;
      font-display: swap;
    }
  </style>
</head>

<body class="body is--cursor animate__animated animate__fadeIn">
  <section class="styleguide">
    <div class="container is--medium">
      <div class="styleguide__list">
        <h1>Failed To Deploy</h1>
        <h4>The Webstore Could Not Be Found</h4>
        <p>Please redeploy your website.</p>
        <div class="line"></div>

      </div>
    </div>
  </section>
  <div
    class="osmo-ui">
    <header class="header">
      <div class="container is--full">
        <nav class="nav-row">
          <a href="#" onClick="history.go(-1); return false;" aria-label="home" target="_blank" class="nav-logo-row w-inline-block">
            <img src="@rescources/site/varsitymarket-technologies/" style="width:100%; max-width:2.5rem; ">
          </a>
          <div class="nav-row__right">
            <a id="ext-dennis" href="https://varsitymarket.tech/" target="_blank" class="website-link is--alt w-inline-block">
              <div class="website-link__arrow"></div>
              <p class="inline-link__p">Error Code: <strong>500</strong></p>
            </a>
          </div>
        </nav>
      </div>
      <div class="osmo-ui__bg is--header"></div>
    </header>
    <footer class="footer">
      <div class="container is--full">
        <div class="footer-row">
          <div class="cloneable-title">
            <p class="cloneable-title__nr">Powered by</p>
            <h1 class="cloneable-title__h1">Varsity Market Technologies</h1>
            <div class="cloneable-title__gradient"></div>
          </div>

        </div>
      </div>
      <div class="osmo-ui__bg"></div>
    </footer>
  </div>
</body>

</html>