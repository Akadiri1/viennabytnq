<?php           // Fetch video and text from database
$homeVideo = selectContent($conn, 'home_video', ['visibility' => 'show']);
$videoSrc = $homeVideo[0]['video_url'];
$videoText = $homeVideo[0]['video_text'];
?>
<html class="js" lang="en">
      <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="theme-color" content="#aaaaaa">
    <meta name="format-detection" content="telephone=no">
    <meta http-equiv="Permissions-Policy" content="geolocation=(self)">
    <link rel="canonical" href="/home">
    <link rel="prefetch" as="document" href="/home">
    <title>
      VIENNA-RTW Exclusive Collections | Stylish
    </title>
    <meta name="description" content="Discover VIENNA-RTW Fashion's curated collection of stylish female clothing. From chic everyday wear to elegant occasion outfits, shop quality designs that empower and inspire women.">
    <link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>">
    <link rel="apple-touch-icon-precomposed" type="image/png" sizes="152x152" href="<?=$logo_directory?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#2d2a6e">
    <meta name="google-site-verification" content="TQoC3V9UXCYDRDAHG11QtYDYdzs6nJQdcblEpCL__WI">
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="allcss/1.css">
    <link rel="stylesheet" href="allcss/2.css">
    <link rel="stylesheet" href="allcss/3.css">
    <link rel="stylesheet" href="allcss/4.css">
    <link rel="stylesheet" href="allcss/5.css">
    <link rel="stylesheet" href="allcss/6.css">
    <link rel="stylesheet" href="allcss/7.css">
    <link rel="stylesheet" href="allcss/8.css">
    <link rel="stylesheet" href="allcss/9.css">
    <link rel="stylesheet" href="allcss/10.css">
    <link rel="stylesheet" href="allcss/11.css">
    <link rel="stylesheet" href="allcss/12.css">
    <link rel="stylesheet" href="allcss/13.css">
    <link rel="stylesheet" href="allcss/14.css">
    <link rel="stylesheet" href="allcss/15.css">
    <link rel="stylesheet" href="allcss/16.css">
    <link rel="stylesheet" href="allcss/17.css">
    <link rel="stylesheet" href="allcss/18.css">
    <link rel="stylesheet" href="allcss/19.css">
    <link rel="stylesheet" href="allcss/20.css">
    <!-- Vienna Modern Design System -->
    <link rel="stylesheet" href="allcss/vienna-modern.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" integrity="sha512-DxV+EoADOkOygM4IR9yXP8Sb2qwgidEmeqAEmDKIOfPRQZOWbXCzLC6vjbZyy0vPisbH2SyW27+ddLVCN+OMzQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- END app block -->
    <meta property="og:image" content="<?=$logo_directory?>">
    <meta property="og:image:secure_url" content="<?=$logo_directory?>">
    <meta property="og:image:width" content="3016">
    <meta property="og:image:height" content="4528">
    <!-- <link href="https://monorail-edge.shopifysvc.com/" rel="dns-prefetch"> -->
    <script>
      (function () {
        if ("sendBeacon" in navigator && "performance" in window) {
          var session_token = document.cookie.match(/_shopify_s=([^;]*)/);
          function handle_abandonment_event(e) {
            var entries = performance.getEntries().filter(function (entry) {
              return /monorail-edge.shopifysvc.com/.test(entry.name);
            });
            if (!window.abandonment_tracked && entries.length === 0) {
              window.abandonment_tracked = true;
              var currentMs = Date.now();
              var navigation_start = performance.timing.navigationStart;
              var payload = {
                shop_id: 74155000035,
                url: window.location.href,
                navigation_start,
                duration: currentMs - navigation_start,
                session_token:
                  session_token && session_token.length === 2
                    ? session_token[1]
                    : "",
                page_type: "index",
              };
              window.navigator.sendBeacon(
                "https://monorail-edge.shopifysvc.com/v1/produce",
                JSON.stringify({
                  schema_id: "online_store_buyer_site_abandonment/1.1",
                  payload: payload,
                  metadata: {
                    event_created_at_ms: currentMs,
                    event_sent_at_ms: currentMs,
                  },
                })
              );
            }
          }
          window.addEventListener("pagehide", handle_abandonment_event);
        }
      })();
    </script>
    <script id="web-pixels-manager-setup">
      (function e(e, d, r, n, o, i) {
        if (
          (void 0 === i && (i = {}),
          !Boolean(
            null ===
              (t =
                null === (a = window.Shopify) || void 0 === a
                  ? void 0
                  : a.analytics) || void 0 === t
              ? void 0
              : t.replayQueue
          ))
        ) {
          var a, t;
          window.Shopify = window.Shopify || {};
          var s = window.Shopify;
          s.analytics = s.analytics || {};
          var l = s.analytics;
          (l.replayQueue = []),
            (l.publish = function (e, d, r) {
              return l.replayQueue.push([e, d, r]), !0;
            });
          try {
            self.performance.mark("wpm:start");
          } catch (e) {}
          var u = (function () {
              var e = {
                  modern:
                    /Edge?\/(1{2}[4-9]|1[2-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Firefox\/(1{2}[4-9]|1[2-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Chrom(ium|e)\/(9{2}|\d{3,})\.\d+(\.\d+|)|(Maci|X1{2}).+ Version\/(15\.\d+|(1[6-9]|[2-9]\d|\d{3,})\.\d+)([,.]\d+|)( \(\w+\)|)( Mobile\/\w+|) Safari\/|Chrome.+OPR\/(9{2}|\d{3,})\.\d+\.\d+|(CPU[ +]OS|iPhone[ +]OS|CPU[ +]iPhone|CPU IPhone OS|CPU iPad OS)[ +]+(15[._]\d+|(1[6-9]|[2-9]\d|\d{3,})[._]\d+)([._]\d+|)|Android:?[ /-](13[3-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})(\.\d+|)(\.\d+|)|Android.+Firefox\/(13[5-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+Chrom(ium|e)\/(13[3-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|SamsungBrowser\/([2-9]\d|\d{3,})\.\d+/,
                  legacy:
                    /Edge?\/(1[6-9]|[2-9]\d|\d{3,})\.\d+(\.\d+|)|Firefox\/(5[4-9]|[6-9]\d|\d{3,})\.\d+(\.\d+|)|Chrom(ium|e)\/(5[1-9]|[6-9]\d|\d{3,})\.\d+(\.\d+|)([\d.]+$|.*Safari\/(?![\d.]+ Edge\/[\d.]+$))|(Maci|X1{2}).+ Version\/(10\.\d+|(1[1-9]|[2-9]\d|\d{3,})\.\d+)([,.]\d+|)( \(\w+\)|)( Mobile\/\w+|) Safari\/|Chrome.+OPR\/(3[89]|[4-9]\d|\d{3,})\.\d+\.\d+|(CPU[ +]OS|iPhone[ +]OS|CPU[ +]iPhone|CPU IPhone OS|CPU iPad OS)[ +]+(10[._]\d+|(1[1-9]|[2-9]\d|\d{3,})[._]\d+)([._]\d+|)|Android:?[ /-](13[3-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})(\.\d+|)(\.\d+|)|Mobile Safari.+OPR\/([89]\d|\d{3,})\.\d+\.\d+|Android.+Firefox\/(13[5-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+Chrom(ium|e)\/(13[3-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+(UC? ?Browser|UCWEB|U3)[ /]?(15\.([5-9]|\d{2,})|(1[6-9]|[2-9]\d|\d{3,})\.\d+)\.\d+|SamsungBrowser\/(5\.\d+|([6-9]|\d{2,})\.\d+)|Android.+MQ{2}Browser\/(14(\.(9|\d{2,})|)|(1[5-9]|[2-9]\d|\d{3,})(\.\d+|))(\.\d+|)|K[Aa][Ii]OS\/(3\.\d+|([4-9]|\d{2,})\.\d+)(\.\d+|)/,
                },
                d = e.modern,
                r = e.legacy,
                n = navigator.userAgent;
              return n.match(d) ? "modern" : n.match(r) ? "legacy" : "unknown";
            })(),
            c = "modern" === u ? "modern" : "legacy",
            f = (null != o ? o : { modern: "", legacy: "" })[c],
            m = (function (e) {
              return [
                e.baseUrl,
                "/wpm",
                "/b",
                e.hashVersion,
                "modern" === e.buildTarget ? "m" : "l",
                ".js",
              ].join("");
            })({ baseUrl: r, hashVersion: n, buildTarget: c }),
            p = (function (e) {
              var d = e.version,
                r = e.bundleTarget,
                n = e.surface,
                o = e.pageUrl,
                i = e.monorailEndpoint;
              return {
                emit: function (e) {
                  var a = e.status,
                    t = e.errorMsg,
                    s = new Date().getTime(),
                    l = JSON.stringify({
                      metadata: { event_sent_at_ms: s },
                      events: [
                        {
                          schema_id: "web_pixels_manager_load/3.1",
                          payload: {
                            version: d,
                            bundle_target: r,
                            page_url: o,
                            status: a,
                            surface: n,
                            error_msg: t,
                          },
                          metadata: { event_created_at_ms: s },
                        },
                      ],
                    });
                  if (!i)
                    return (
                      console &&
                        console.warn &&
                        console.warn(
                          "[Web Pixels Manager] No Monorail endpoint provided, skipping logging."
                        ),
                      !1
                    );
                  try {
                    return self.navigator.sendBeacon.bind(self.navigator)(i, l);
                  } catch (e) {}
                  var u = new XMLHttpRequest();
                  try {
                    return (
                      u.open("POST.html", i, !0),
                      u.setRequestHeader("Content-Type", "text/plain"),
                      u.send(l),
                      !0
                    );
                  } catch (e) {
                    return (
                      console &&
                        console.warn &&
                        console.warn(
                          "[Web Pixels Manager] Got an unhandled error while logging to Monorail."
                        ),
                      !1
                    );
                  }
                },
              };
            })({
              version: n,
              bundleTarget: u,
              surface: e.surface,
              pageUrl: self.location.href,
              monorailEndpoint: e.monorailEndpoint,
            });
          try {
            (i.browserTarget = u),
              (function (e) {
                var d = e.src,
                  r = e.async,
                  n = void 0 === r || r,
                  o = e.onload,
                  i = e.onerror,
                  a = e.sri,
                  t = e.scriptDataAttributes,
                  s = void 0 === t ? {} : t,
                  l = document.createElement("script"),
                  u = document.querySelector("head"),
                  c = document.querySelector("body");
                if (
                  ((l.async = n),
                  (l.src = d),
                  a && ((l.integrity = a), (l.crossOrigin = "anonymous")),
                  s)
                )
                  for (var f in s)
                    if (Object.prototype.hasOwnProperty.call(s, f))
                      try {
                        l.dataset[f] = s[f];
                      } catch (e) {}
                if (
                  (o && l.addEventListener("load", o),
                  i && l.addEventListener("error", i),
                  u)
                )
                  u.appendChild(l);
                else {
                  if (!c)
                    throw new Error(
                      "Did not find a head or body element to append the script"
                    );
                  c.appendChild(l);
                }
              })({
                src: m,
                async: !0,
                onload: function () {
                  if (
                    !(function () {
                      var e, d;
                      return Boolean(
                        null ===
                          (d =
                            null === (e = window.Shopify) || void 0 === e
                              ? void 0
                              : e.analytics) || void 0 === d
                          ? void 0
                          : d.initialized
                      );
                    })()
                  ) {
                    var r = window.webPixelsManager.init(e) || void 0;
                    if (r) {
                      d(r);
                      var n = window.Shopify.analytics;
                      n.replayQueue.forEach(function (e) {
                        var d = e[0],
                          n = e[1],
                          o = e[2];
                        r.publishCustomEvent(d, n, o);
                      }),
                        (n.replayQueue = []),
                        (n.publish = r.publishCustomEvent),
                        (n.visitor = r.visitor),
                        (n.initialized = !0);
                    }
                  }
                },
                onerror: function () {
                  return p.emit({
                    status: "failed",
                    errorMsg: "".concat(m, " has failed to load"),
                  });
                },
                sri: (function (e) {
                  var d = /^sha384-[A-Za-z0-9+/=]+$/;
                  return "string" == typeof e && d.test(e);
                })(f)
                  ? f
                  : "",
                scriptDataAttributes: i,
              }),
              p.emit({ status: "loading" });
          } catch (e) {
            p.emit({
              status: "failed",
              errorMsg: (null == e ? void 0 : e.message) || "Unknown error",
            });
          }
        }
      })(
        {
          shopId: 74155000035,
          storefrontBaseUrl: "https://VIENNA-RTW.co",
          extensionsBaseUrl:
            "https://extensions.shopifycdn.com/cdn/shopifycloud/web-pixels-manager",
          monorailEndpoint:
            "https://monorail-edge.shopifysvc.com/unstable/produce_batch",
          surface: "storefront-renderer",
          enabledBetaFlags: ["ac843a20"],
          webPixelsConfigList: [
            {
              id: "1004568803",
              configuration:
                '{"pixel_id":"1061724202631906","pixel_type":"facebook_pixel"}',
              eventPayloadVersion: "v1",
              runtimeContext: "OPEN",
              scriptVersion: "6d8c3ef0426b37b2a9b717daeb719f58",
              type: "APP",
              apiClientId: 2329312,
              privacyPurposes: ["ANALYTICS", "MARKETING", "SALE_OF_DATA"],
            },
            {
              id: "945651939",
              configuration: '{"accountID":"S4WJnH"}',
              eventPayloadVersion: "v1",
              runtimeContext: "STRICT",
              scriptVersion: "cbf4a80740670c5ae93d33dc67925b9a",
              type: "APP",
              apiClientId: 123074,
              privacyPurposes: ["ANALYTICS", "MARKETING"],
            },
            {
              id: "shopify-app-pixel",
              configuration: "{}",
              eventPayloadVersion: "v1",
              runtimeContext: "STRICT",
              scriptVersion: "0440",
              apiClientId: "shopify-pixel",
              type: "APP",
              privacyPurposes: ["ANALYTICS", "MARKETING"],
            },
            {
              id: "shopify-custom-pixel",
              eventPayloadVersion: "v1",
              runtimeContext: "LAX",
              scriptVersion: "0440",
              apiClientId: "shopify-pixel",
              type: "CUSTOM",
              privacyPurposes: ["ANALYTICS", "MARKETING"],
            },
          ],
          isMerchantRequest: false,
          initData: {
            shop: {
              name: "VIENNA-RTW US",
              paymentSettings: { currencyCode: "USD" },
              myshopifyDomain: "VIENNA-RTW-us.myshopify.com",
              countryCode: "GB",
              storefrontUrl: "https://VIENNA-RTW.co",
            },
            customer: null,
            cart: null,
            checkout: null,
            productVariants: [],
            purchasingCompany: null,
          },
        },
        function pageEvents(webPixelsManagerAPI) {
          webPixelsManagerAPI.publish("page_viewed", {});
        },
        "cdn.html",
        "aa986369w89f019d8pedb14a83mf698ddc1",
        { modern: "", legacy: "" },
        {
          shopId: "74155000035",
          storefrontBaseUrl: "https://VIENNA-RTW.co",
          extensionBaseUrl:
            "https://extensions.shopifycdn.com/cdn/shopifycloud/web-pixels-manager",
          surface: "storefront-renderer",
          enabledBetaFlags: '["ac843a20"]',
          isMerchantRequest: "false",
          hashVersion: "aa986369w89f019d8pedb14a83mf698ddc1",
          publish: "custom",
        }
      );
    </script><script async="" src="cdn.html/wpm/baa986369w89f019d8pedb14a83mf698ddc1m.js" data-shop-id="74155000035" data-storefront-base-url="https://VIENNA-RTW.co" data-extension-base-url="https://extensions.shopifycdn.com/cdn/shopifycloud/web-pixels-manager" data-surface="storefront-renderer" data-enabled-beta-flags="[&quot;ac843a20&quot;]" data-is-merchant-request="false" data-hash-version="aa986369w89f019d8pedb14a83mf698ddc1" data-publish="custom" data-browser-target="modern"></script>
    <script>
      window.ShopifyAnalytics = window.ShopifyAnalytics || {};
      window.ShopifyAnalytics.meta = window.ShopifyAnalytics.meta || {};
      window.ShopifyAnalytics.meta.currency = "USD";
      var meta = { page: { pageType: "home" } };
      for (var attr in meta) {
        window.ShopifyAnalytics.meta[attr] = meta[attr];
      }
    </script>
    <script class="analytics">
      (function () {
        var customDocumentWrite = function (content) {
          var jquery = null;

          if (window.jQuery) {
            jquery = window.jQuery;
          } else if (window.Checkout && window.Checkout.$) {
            jquery = window.Checkout.$;
          }

          if (jquery) {
            jquery("body").append(content);
          }
        };

        var hasLoggedConversion = function (token) {
          if (token) {
            return document.cookie.indexOf("loggedConversion=" + token) !== -1;
          }
          return false;
        };

        var setCookieIfConversion = function (token) {
          if (token) {
            var twoMonthsFromNow = new Date(Date.now());
            twoMonthsFromNow.setMonth(twoMonthsFromNow.getMonth() + 2);

            document.cookie =
              "loggedConversion=" + token + "; expires=" + twoMonthsFromNow;
          }
        };

        var trekkie =
          (window.ShopifyAnalytics.lib =
          window.trekkie =
            window.trekkie || []);
        if (trekkie.integrations) {
          return;
        }
        trekkie.methods = [
          "identify",
          "page",
          "ready",
          "track",
          "trackForm",
          "trackLink",
        ];
        trekkie.factory = function (method) {
          return function () {
            var args = Array.prototype.slice.call(arguments);
            args.unshift(method);
            trekkie.push(args);
            return trekkie;
          };
        };
        for (var i = 0; i < trekkie.methods.length; i++) {
          var key = trekkie.methods[i];
          trekkie[key] = trekkie.factory(key);
        }
        trekkie.load = function (config) {
          trekkie.config = config || {};
          trekkie.config.initialDocumentCookie = document.cookie;
          var first = document.getElementsByTagName("script")[0];
          var script = document.createElement("script");
          script.type = "text/javascript";
          script.onerror = function (e) {
            var scriptFallback = document.createElement("script");
            scriptFallback.type = "text/javascript";
            scriptFallback.onerror = function (error) {
              var Monorail = {
                produce: function produce(monorailDomain, schemaId, payload) {
                  var currentMs = new Date().getTime();
                  var event = {
                    schema_id: schemaId,
                    payload: payload,
                    metadata: {
                      event_created_at_ms: currentMs,
                      event_sent_at_ms: currentMs,
                    },
                  };
                  return Monorail.sendRequest(
                    "https://" + monorailDomain + "/v1/produce",
                    JSON.stringify(event)
                  );
                },
                sendRequest: function sendRequest(endpointUrl, payload) {
                  // Try the sendBeacon API
                  if (
                    window &&
                    window.navigator &&
                    typeof window.navigator.sendBeacon === "function" &&
                    typeof window.Blob === "function" &&
                    !Monorail.isIos12()
                  ) {
                    var blobData = new window.Blob([payload], {
                      type: "text/plain",
                    });

                    if (window.navigator.sendBeacon(endpointUrl, blobData)) {
                      return true;
                    } // sendBeacon was not successful
                  } // XHR beacon

                  var xhr = new XMLHttpRequest();

                  try {
                    xhr.open("POST.html", endpointUrl);
                    xhr.setRequestHeader("Content-Type", "text/plain");
                    xhr.send(payload);
                  } catch (e) {
                    console.log(e);
                  }

                  return false;
                },
                isIos12: function isIos12() {
                  return (
                    window.navigator.userAgent.lastIndexOf(
                      "iPhone; CPU iPhone OS 12_"
                    ) !== -1 ||
                    window.navigator.userAgent.lastIndexOf(
                      "iPad; CPU OS 12_"
                    ) !== -1
                  );
                },
              };
              Monorail.produce(
                "monorail-edge.shopifysvc.com",
                "trekkie_storefront_load_errors/1.1",
                {
                  shop_id: 74155000035,
                  theme_id: 148522795235,
                  app_name: "storefront",
                  context_url: window.location.href,
                  source_url:
                    "//VIENNA-RTW.co/cdn/s/trekkie.storefront.85527fa5792f2e0cb2c2b51738712be68613edc8.min.js",
                }
              );
            };
            scriptFallback.async = true;
            scriptFallback.src =
              "cdn/s/trekkie.storefront.85527fa5792f2e0cb2c2b51738712be68613edc8.min.js";
            first.parentNode.insertBefore(scriptFallback, first);
          };
          script.async = true;
          script.src =
            "cdn/s/trekkie.storefront.85527fa5792f2e0cb2c2b51738712be68613edc8.min.js";
          first.parentNode.insertBefore(script, first);
        };
        trekkie.load({
          Trekkie: {
            appName: "storefront",
            development: false,
            defaultAttributes: {
              shopId: 74155000035,
              isMerchantRequest: null,
              themeId: 148522795235,
              themeCityHash: "14819533787138664124",
              contentLanguage: "en",
              currency: "USD",
              eventMetadataId: "4108793a-f830-4e55-9ca1-6b83202299f7",
            },
            isServerSideCookieWritingEnabled: true,
            monorailRegion: "shop_domain",
          },
          "Session Attribution": {},
          S2S: {
            facebookCapiEnabled: true,
            source: "trekkie-storefront-renderer",
            apiClientId: 580111,
          },
        });

        var loaded = false;
        trekkie.ready(function () {
          if (loaded) return;
          loaded = true;

          window.ShopifyAnalytics.lib = window.trekkie;

          var originalDocumentWrite = document.write;
          document.write = customDocumentWrite;
          try {
            window.ShopifyAnalytics.merchantGoogleAnalytics.call(this);
          } catch (error) {}
          document.write = originalDocumentWrite;

          window.ShopifyAnalytics.lib.page(null, {
            pageType: "home",
            shopifyEmitted: true,
          });

          var match = window.location.pathname.match(
            /checkouts\/(.+)\/(thank_you|post_purchase)/
          );
          var token = match ? match[1] : undefined;
          if (!hasLoggedConversion(token)) {
            setCookieIfConversion(token);
          }
        });

        var eventsListenerScript = document.createElement("script");
        eventsListenerScript.async = true;
        eventsListenerScript.src =
          "cdn/shopifycloud/storefront/assets/shop_events_listener-8675b082.js";
        document
          .getElementsByTagName("head")[0]
          .appendChild(eventsListenerScript);
      })();
    </script>
    <script async="" src="cdn/shopifycloud/storefront/assets/shop_events_listener-8675b082.js"></script>
    <script defer="" src="cdn/shopifycloud/perf-kit/shopify-perf-kit-1.6.6.min.js" data-application="storefront-renderer" data-shop-id="74155000035" data-render-region="gcp-europe-west1" data-page-type="index" data-theme-instance-id="148522795235" data-theme-name="VIENNA-RTW Shopify" data-theme-version="2.0.0" data-monorail-region="shop_domain" data-resource-timing-sampling-rate="10" data-shs="true"></script>
  <style> .marquee-container { overflow: hidden; background-color: #000; color: #fff !important; padding: 5px 10px; font-size: 14px; font-weight: 300; width: 100%; z-index: 9999; }  .marquee-content { display: inline-block; white-space: nowrap; padding-left: 100%; will-change: transform; animation: marquee-scroll 40s linear infinite; }  .marquee-container:hover .marquee-content { animation-play-state: paused; }  @keyframes marquee-scroll { 0% { transform: translateX(0%); } 100% { transform: translateX(-100%); } } </style><script type="module" defer="" src="https://VIENNA-RTW.co/cdn/shopifycloud/media-analytics/v0.1/analytics.js"></script><link rel="dns-prefetch preconnect" href="https://cdn.shopify.com" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/polyfills-legacy.DTu4b8LP.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/app-legacy.D_DgBTLc.js" crossorigin=""><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><style></style><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/page-OnePage-legacy.DR5rHWbc.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/DeliveryMethodSelectorSection-legacy._-pINcGs.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/useEditorShopPayNavigation-legacy.FVUvl6Rf.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/VaultedPayment-legacy.D1cc4txL.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/LocalizationExtensionField-legacy.DPUrd3oE.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/ShopPayOptInDisclaimer-legacy.CoDqklHd.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/ShipmentBreakdown-legacy.BP-9GHWx.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/MerchandiseModal-legacy.Du24LH7p.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/StackedMerchandisePreview-legacy.CdkYOSFG.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/PayButtonSection-legacy.aHis-7dq.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/component-ShopPayVerificationSwitch-legacy.ZbO-2wCp.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/useSubscribeMessenger-legacy.MwcUof7v.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/index-legacy.DmeyDv1f.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="image" href="https://cdn.shopify.com/s/files/1/0741/5500/0035/files/VIENNA-RTW_icon_white_x320.png?v=1751488305" crossorigin=""></head>
 
  <body id="VIENNA-RTW-fashion-exclusive-collections-for-every-occasion" class="home template-index" data-header="1">

  <audio src="images/shopping.mp3" autoplay hidden></audio>
  <!-- =================================================================== -->
<!-- PRELOADER START                                                     -->
<!-- =================================================================== -->
<div id="preloader">
    <div class="preloader-content">
        <!-- Make sure the path to your logo is correct -->
        <img src="<?=$logo_directory?>" alt="VIENNA Logo" class="preloader-logo">
        <br>
        <div class="preloader-text"><?=$site_name?></div>
    </div>
</div>

<style>

#preloader {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background-color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.8s ease-in-out, visibility 0.8s ease-in-out;
    opacity: 1;
    visibility: visible;
}

#preloader.loaded {
    opacity: 0;
    visibility: hidden;
}

.preloader-content {
    text-align: center;
}

/* --- Logo Animation --- */
.preloader-logo {
    width: 80px;
    height: auto;
    filter: #000;
    opacity: 0; /* Start hidden */
    animation: fadeIn 1.5s ease-out forwards;
}

@keyframes fadeIn {
    to {
        opacity: 1;
    }
}

/* --- Smooth Writing Effect --- */
.preloader-text {
    color: #000;
    font-size: 1rem;
    font-weight: 300;
    text-transform: uppercase;
    letter-spacing: 0.3em;
    opacity: 0.8;

    /* --- Core Smooth Reveal CSS --- */
    display: inline-block;
    overflow: hidden;      /* Ensures text is revealed as width expands */
    white-space: nowrap;   /* Keeps the text on a single line */
    width: 0;              /* Start with no width */
    
    /* --- MODIFICATION 1: Use a smooth timing function and remove the blink animation --- */
    /* Animation: name, duration, timing-function, delay, fill-mode */
    animation: 
        typing 2.5s ease-in-out 1.5s forwards;
}

/* Typing animation (expands width) */
@keyframes typing {
    from { width: 0; }
    to { width: 100%; } /* This will now animate smoothly */
}

/* --- MODIFICATION 2: The blink-cursor keyframes are no longer needed --- */
/*
@keyframes blink-cursor {
    from, to { border-color: transparent }
    50% { border-color: rgba(255, 255, 255, 0.75); }
}
*/

/* Class to prevent scrolling while preloader is active */
body.preloading {
    overflow: hidden;
}
</style>

<script>
    // --- PRELOADER LOGIC (v2 with Minimum Duration) ---

    document.body.classList.add('preloading');

    let isPageLoaded = false;
    let isTimerFinished = false;

    function hidePreloader() {
        // This function will only run when BOTH conditions are true
        if (isPageLoaded && isTimerFinished) {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.classList.add('loaded');
                preloader.addEventListener('transitionend', function() {
                    document.body.classList.remove('preloading');
                    // Optional: remove the preloader element completely after it has faded out
                    // this.remove(); 
                });
            }
        }
    }

    // 1. Listen for the page to be fully loaded (images, scripts, etc.)
    window.addEventListener('load', function() {
        isPageLoaded = true;
        hidePreloader(); // Attempt to hide preloader
    });

    // 2. Set a minimum timer of 6 seconds
    setTimeout(function() {
        isTimerFinished = true;
        hidePreloader(); // Attempt to hide preloader
    }, 1000); // 4000 milliseconds = 4 seconds

</script>
    <div class="page-load circle-loadding" style="display: none;"><span></span></div>
    <div class="main" style="transform: none">
      <header id="header">
        <div id="shopify-section-top-bar" class="shopify-section cms-top-header cms-top-bar">
          <style data-shopify="">
            :root {
              --bg-top-bar: #1a1a1a;
              --color-text-top-bar: #ffffff;
            }
          </style>
        </div>
        <div id="shopify-section-top-header" class="shopify-section cms-top-header">
          <style data-shopify="">
            :root {
              --bg-top-header: #d93939;
              --color-text-top-header: #ffffff;
              --color-text-top-header-hover: #efad2a;
            }
          </style>
        </div>
        <div id="shopify-section-header1" class="shopify-section header_megamenu">
          <div data-section-id="header1" data-section-type="header-section">
            <div class="header logo-left-menu-center header-1-lines">
              <div id="main-site-header">
                <div class="header-nav-desktop">
                 <!--  <div class="js-mobile-menu menu-bar desktop-navigation">
                    <span class="icon">
                      <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                          <style>
                            .cls-1 {
                              fill: none;
                            }
                          </style>
                        </defs>
                        <title></title>
                        <g data-name="Layer 2" id="Layer_2">
                          <path d="M28,10H4A1,1,0,0,1,4,8H28a1,1,0,0,1,0,2Z"></path>
                          <path d="M28,17H4a1,1,0,0,1,0-2H28a1,1,0,0,1,0,2Z"></path>
                          <path d="M28,24H4a1,1,0,0,1,0-2H28a1,1,0,0,1,0,2Z"></path>
                        </g>
                        <g id="frame">
                          <rect class="cls-1" height="32" width="32"></rect>
                        </g>
                      </svg>
                    </span>
                  </div> --> 
                </div>
                <a href="/home" aria-label="VIENNA-RTW US" class="logo-slogan">
                    <!-- We removed the incorrect "color" style and added the CSS filter -->
                    <img 
                      src="<?=$logo_directory?>" 
                      alt="VIENNA-RTW US" 
                      style="max-width: 140px; height: auto; filter: brightness(0) invert(1);">
                    <span>VIENNA BY TNQ</span>
                  </a>
                  <?php
// Start the session to remember the user's choice on the server
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function getLiveUsdToNgnRate() {
    // Try multiple free exchange rate APIs
    $apis = [
        'https://open.er-api.com/v6/latest/USD',
        'https://api.exchangerate-api.com/v4/latest/USD'
    ];
    
    foreach ($apis as $apiUrl) {
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $response = @file_get_contents($apiUrl, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['rates']['NGN'])) {
                return floatval($data['rates']['NGN']);
            }
        }
    }
    
    // Fallback in case all APIs fail - use current market rate (Jan 2025)
    return 1480; // Current market rate (Jan 2025)
}

// Define exchange rate constant (only once)
if (!defined('USD_EXCHANGE_RATE')) {
    define('USD_EXCHANGE_RATE', getLiveUsdToNgnRate());
}

// Set the active currency
if (isset($_SESSION['currency'])) {
    $current_currency = $_SESSION['currency'];
} elseif (isset($_COOKIE['user_currency'])) {
    $current_currency = $_COOKIE['user_currency'];
} else {
    $current_currency = 'NGN'; // Default currency
}
// ===================================================================
?>

<!-- Add some simple styling for the currency switcher -->
<style>
    .currency-switcher a {
        color: #fff; /* brand-gray */
        font-weight: 500;
        transition: color 0.2s ease-in-out;
    }
    .currency-switcher a:hover {
        color: #1A1A1A; /* brand-text */
    }
    .currency-switcher a.active {
        color: #fff; /* brand-text */
        font-weight: 700;
        text-decoration: underline;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {

    // --- CURRENCY SWITCHER LOGIC ---

    // 1. Get variables from PHP
    const USD_RATE = <?= USD_EXCHANGE_RATE ?>;
    const INITIAL_CURRENCY = '<?= $current_currency ?>';

    const currencyLinks = document.querySelectorAll('.currency-switcher a.currency-link');
    
    /**
     * Formats a number into a currency string (e.g., $27.59 or ₦40,000.00)
     * @param {number} amount - The numeric value.
     * @param {string} currency - 'USD' or 'NGN'.
     * @returns {string} The formatted currency string.
     */
    function formatPrice(amount, currency) {
        if (currency === 'USD') {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
        }
        // Default to NGN
        return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount).replace('NGN', '₦');
    }

    /**
     * Finds all price elements on the page and updates them to the target currency.
     * @param {string} targetCurrency - The currency to convert to ('USD' or 'NGN').
     */
    function updateAllPrices(targetCurrency) {
        const priceElements = document.querySelectorAll('.price-display');

        priceElements.forEach(el => {
            const ngnPrice = parseFloat(el.dataset.priceNgn);
            if (!isNaN(ngnPrice)) {
                let newPrice = ngnPrice;
                if (targetCurrency === 'USD') {
                    newPrice = ngnPrice / USD_RATE;
                }
                el.textContent = formatPrice(newPrice, targetCurrency);
            }
        });
    }

    // 2. Add click listeners to the currency links
    currencyLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetCurrency = this.dataset.currency;

            // Update the active link style
            currencyLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            // Convert all prices on the page
            updateAllPrices(targetCurrency);

            // Set a cookie to remember the choice for 30 days
            const expiryDate = new Date();
            expiryDate.setDate(expiryDate.getDate() + 30);
            document.cookie = `user_currency=${targetCurrency}; expires=${expiryDate.toUTCString()}; path=/`;

            // Inform the server to update the session
            fetch('/currency', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ currency: targetCurrency })
            });
        });
    });

    // 3. On page load, if the initial currency is USD, run the conversion immediately.
    if (INITIAL_CURRENCY === 'USD') {
        updateAllPrices('USD');
    }

});
</script>
                <div class="controls-container">
                <ul class="header-control">
                    
                    <!-- NEW: Currency Switcher -->
                    <li class="currency-switcher mr-4">
                        <a href="#" class="currency-link <?= ($current_currency === 'NGN') ? 'active' : '' ?>" data-currency="NGN">NGN</a>
                        <span class="mx-1">/</span>
                        <a href="#" class="currency-link <?= ($current_currency === 'USD') ? 'active' : '' ?>" data-currency="USD">USD</a>
                    </li>
                    <!-- End of Currency Switcher -->
                </ul>
            </div>
              </div>
              <style>
                .controls-container {
                  position: fixed;
                  right: 10px;
                  z-index: 999;
                }
                .header-nav-desktop {
                  cursor: pointer;
                  z-index: 999;
                  position: fixed;
                }
                .desktop-navigation {
                  width: 50px;
                  height: 35px;
                  align-self: center;
                  text-align: center;
                  justify-content: center;
                  display: flex;
                  align-items: center;
                }
                #main-site-header {
                  position: fixed;
                  left: 0;
                  right: 0;
                  width: 100%;
                  display: flex;
                  align-items: center;
                  padding: 10px;
                  justify-content: space-between;
                  top: 0px;
                  height: 90px;
                  background-color: white;
                  padding-bottom: 25px;
                }
                .home #main-site-header {
                  background-color: transparent;
                }
                .logo-slogan {
                  position: fixed;
                  display: flex;
                  flex-direction: column;
                  align-items: center;
                  color: white;
                  width: 180px;
                  align-self: center;
                  width: 100%;
                }
                .logo-slogan img {
                  filter: brightness(0) invert(0);
                  max-width: 70px !important;
                  margin-top: 15px;
                }
                .logo-slogan span {
                  color: fff;
                }
                .main {
                  transform: none;
                  padding-top: 150px;
                }
                .home .logo-slogan img {
                  filter: brightness(0) invert(1);
                }
                .home .logo-slogan span {
                  color: white;
                }
                .home .main {
                  padding-top: 0;
                }
                .header-nav-desktop svg {
                  fill: fff;
                  width: 32px !important;
                  height: 32px !important;
                }
                .header-nav-desktop {
                  fill: white;
                  width: 32px;
                  height: 32px;
                }
                .home .header-nav-desktop svg {
                  fill: white;
                  width: 32px;
                  height: 32px;
                }
                .home #main-site-header i {
                  fill: white;
                  color: white;
                }
                #main-site-header i {
                  color: fff;
                }
                #mobile_menu .laber_mb_menu .menu-item {
                  border: none;
                  position: relative;
                }
                #mobile_menu .laber_mb_menu .menu-item .collapse {
                  line-height: 25px;
                  height: 25px;
                }
                #mobile_menu .laber_mb_menu .menu-item a {
                  position: relative;
                  font-size: 10px;
                  height: 25px;
                }
                .header .header-icon svg {
                  width: 26px;
                  fill: fff !important;
                }
                .home .header .header-icon svg {
                  fill: white !important;
                }
              </style>
            </div>
          </div>
          <style data-shopify="">
            :root {
              --color-bg-header: #ffffff;
              --color-bg-header-sticky: #ffffff;
              --color-icon-header: #fff;
              --color-count-header: #d93939;
            }
          </style>
        </div>
        <!-- <div id="marquee-notice" style="width: 100%; z-index: 1;    margin-top: -50px;"></div> -->
      </header>
      <div class="page-container clearfix" id="PageContainer">
        <main id="MainContent">
          <div class="main-content home-page main-content-homedefault">
            <!-- BEGIN content_for_index -->
            <section id="shopify-section-video_background_iWWUYE" class="shopify-section video-section">
              <div class="video-wrapper">
                <video autoplay="" muted="" loop="" playsinline="" preload="auto" class="background-video" poster="Liquid%20error%20(sections/video-background%20line%20169)_%20invalid%20url%20input.html" onerror="this.style.display='none'; document.querySelector('.fallback-image').style.display = 'block';">
                  <!-- <source src="https://cdn.shopify.com/videos/c/o/v/fa9a63b71e474a97bf6587a86a849e25.mp4" type="video/mp4"> -->
                  <source src="<?=$videoSrc;?>" type="video/mp4">

                  Your browser does not support the video tag.
                </video>
                <div class="fallback-image" style="
                    background-image: url(Liquid%20error%20(
                        sections/video-background%20line%20176.html
                      ):invalidurlinput);"></div>
                <div class="overlay" style="background-color: #000000; opacity: 0.3"></div>
                <div class="content" style="color: #ffffff">
                  <h1 class="heading-text fade-in" style="color: #ffffff; font-size: 48px">
                    <?=$videoText;?>
                  </h1>
                  <!-- <img src="images/vienna.jpg" alt="Overlay Subtitle" class="overlay-subtitle-image fade-in" style="width: 60%"> -->
                </div>
                </div>
                <!-- Scroll Indicator -->
                <div class="scroll-indicator">
                  <span>Scroll</span>
                </div>
              <style>
                .video-section {
                  position: relative;
                  height: 100vh;
                  overflow: hidden;
                }
                .video-wrapper {
                  position: relative;
                  width: 100%;
                  height: 100%;
                }
                .background-video {
                  display: block;
                  width: 100%;
                  height: 100%;
                  object-fit: cover;
                  object-position: center center;
                  position: absolute;
                  top: 0;
                  left: 0;
                  z-index: 1;
                }
                .fallback-image {
                  display: none;
                  background-size: cover;
                  background-position: center;
                  width: 100%;
                  height: 100%;
                  position: absolute;
                  top: 0;
                  left: 0;
                  z-index: 1;
                }
                .overlay {
                  position: absolute;
                  width: 100%;
                  height: 100%;
                  z-index: 2;
                }
                .content {
                  position: absolute;
                  z-index: 3;
                  text-align: center;
                  top: 50%;
                  left: 50%;
                  transform: translate(-50%, -50%);
                  padding: 0 20px;
                }
                .heading-text {
                  margin-bottom: 10px;
                }
                .overlay-heading-image,
                .overlay-subtitle-image {
                  height: auto;
                  margin-bottom: 20px;
                }
                .subtitle {
                  margin-bottom: 20px;
                }
                .cta-button {
                  padding: 12px 24px;
                  background-color: #ffffff;
                  text-decoration: none;
                  font-weight: bold;
                  border-radius: 6px;
                  transition: background 0.3s ease;
                }
                .cta-button:hover {
                  background-color: #eee;
                }
                @media (max-width: 768px) {
                  .heading-text {
                    font-size: 28px !important;
                  }
                  .subtitle {
                    font-size: 14px !important;
                  }
                  .cta-button {
                    font-size: 14px !important;
                  }
                  .background-video {
                    object-position: center center;
                  }
                }
                .fade-in {
                  animation: fadeInUp 1s ease-out forwards;
                  opacity: 0;
                }
                @keyframes fadeInUp {
                  from {
                    transform: translateY(30px);
                    opacity: 0;
                  }
                  to {
                    transform: translateY(0);
                    opacity: 1;
                  }
                }
              </style>
            </section>

          <div id="shopify-section-collection_featured_grid_ztbCVH"
            class="shopify-section collection_grid cms_section type_collection_grid zoom_img">
            <section id="laber_collection_featured_grid_ztbCVH" class="cat_size_3 pad-5">
              <div class="full-width">

<div class="row">
    <!-- Loop through your collections -->
    <?php 
    $collections = selectContent($conn, "collections", [], "ORDER BY name ASC");

$collectionsWithData = [];
foreach ($collections as $collection) {
    // For each collection, find the first product to get its image
    $productStmt = $conn->prepare("
        SELECT image_one 
        FROM panel_products 
        WHERE collection_id = ? AND visibility = 'show' AND image_one IS NOT NULL AND image_one != '' 
        ORDER BY id DESC LIMIT 1
    ");
    $productStmt->execute([$collection['id']]);
    $firstProductImage = $productStmt->fetchColumn();

    // If an image is found, add it to our collection array. Otherwise, use a placeholder.
    $collection['display_image'] = $firstProductImage ?: 'path/to/your/placeholder.jpg'; // IMPORTANT: Set a fallback image path
    $collectionsWithData[] = $collection;
}
    ?>
    <?php foreach ($collectionsWithData as $collection):?>
        <div id="collection_<?= htmlspecialchars($collection['id']) ?>"
            class="laber_banner cat_grid_item cat_space_item cat_grid_item_1 col-md-4 col-12 pad"
            style="margin-bottom:20px;">
            <div class="cat_grid_item__content">
                <!-- ** MODIFIED: The link now points to the shop page with a collection filter ** -->
                <a href="/shop" class="cat_grid_item__link">
                    <div data-image-effect="" class="pr_lazy_img main-img laber_bg_lz lazyloaded"
                        style="padding-top: 150%; background-image: url('<?= htmlspecialchars($collection['display_image']) ?>');">
                    </div>
                    <span class="icon">
                        <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                            <g data-name="Layer 2"><path d="M19,26a1,1,0,0,1-.71-.29,1,1,0,0,1,0-1.42L26.59,16l-8.3-8.29a1,1,0,0,1,1.42-1.42l9,9a1,1,0,0,1,0,1.42l-9,9A1,1,0,0,1,19,26Z"></path><path d="M28,17H4a1,1,0,0,1,0-2H28a1,1,0,0,1,0,2Z"></path></g>
                        </svg>
                    </span>
                </a>
                <div class="cat_grid_item__wrapper text_center v_bottom h_center">
                    <div class="cat_grid_item__title style_1">
                        <!-- ** MODIFIED: The link and text now use the collection name ** -->
                        <a href="/shop" style="color: white;">
                            <?= htmlspecialchars($collection['name']) ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach;?>
</div>
                </div>
              </div>
            </section>

           
          </div>
            <!-- END content_for_index -->
               <!-- You Might Also Like Section -->
          </div>

          
        </main>
      </div>
    </div>
      <footer id="footer">
        <div id="shopify-section-footer1" class="shopify-section footer_top">
          <section id="laber_footer1">
            <!--Footer-->
            <div class="main-footer">
              <div class="container">
                <div class="row">
                  <div class="footer-iteam col-lg-12 col-md-6 col-12 flex h_center text_center">
                    <aside id="block_logo_footer_G84Lt7" class="widget widget_text">
                      <div class="logo-footer">
                        <a href="/home" class="site-header__logo-image">
                          <img src="<?=$logo_directory?>" alt="" style="max-width: 125px; width: auto; height: auto">
                        </a>
                      </div>
                      <div class="laber-social-link socials clearfix">
                        <ul class="list-socials">
                        <?php foreach ($socialLinks as $key => $value):?>
                            <li class="instagram">
                              <a target="_blank" href="<?=$value['input_link']?>" title="<?=$value['input_name']?>"><i class="<?=$value['input_icon']?>"></i></a>
                            </li>
                        <?php endforeach;?>

                        </ul>
                      </div>
                    </aside>
                  </div>
                  <!-- div class="footer-iteam col-lg-12 col-md-12 col-12 h_center v_top text_center">
                    <aside id="block_mail_QdBmyp" class="widget_mail">
                      <div class="labernewsletter flex h_center v_top text_center">
                        <div class="newsletter-content" style="border-color: ">
                          <h3 class="widget-title">
                            <span class="txt_title">Newsletter Signup</span>
                          </h3>
                          <div class="sub-email" style="color: #ffffff">
                            Subscribe to our newsletter
                          </div>
                          <div class="textwidget widget_footer">
                            <div class="footer-mail">
                              <form method="post" action="" id="contact_form" accept-charset="UTF-8" class="newsletter-form-footer">
                                <input type="hidden" name="form_type" value="customer"><input type="hidden" name="utf8" value="✓"><input type="hidden" name="contact[tags]" value="newsletter">
                                <div class="mc4wp-form-fields">
                                  <div class="signup-newsletter-form">
                                    <div class="col_email">
                                      <div class="icon">
                                        <svg width="18" height="14" viewBox="0 0 18 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                          <path d="M9.10791 1H14.3079C15.8543 1 17.1079 2.2536 17.1079 3.8V10.2C17.1079 11.7464 15.8543 13 14.3079 13H3.90791C2.36151 13 1.10791 11.7464 1.10791 10.2V3.8C1.10791 2.2536 2.36151 1 3.90791 1H5.10791" stroke="#ACAFB7" stroke-width="1.5" stroke-linecap="round"></path>
                                          <path d="M5.10791 5L8.50791 7.55019C8.86348 7.81688 9.3524 7.81688 9.70797 7.55019L13.108 5" stroke="#ACAFB7" stroke-width="1.5" stroke-linecap="round"></path>
                                        </svg>
                                      </div>
                                      <input type="email" name="contact[email]" placeholder="Your email address..." value="" class="input-text" required="required">
                                    </div>
                                    <div class="col_btn">
                                      <button type="submit" class="submit-btn truncate">
                                        <span> Subscribe </span>
                                      </button>
                                    </div>
                                  </div>
                                </div>
                                <div class="mc4wp-response"></div>
                              </form>
                            </div>
                          </div>
                        </div>
                      </div>
                    </aside>
                  </div> -->
                  <style data-shopify="">
                    #block_mail_QdBmyp.widget_mail
                      .widget_footer
                      .signup-newsletter-form
                      .submit-btn {
                      background: #1a1a1a;
                      border-color: #1a1a1a;
                    }
                    #block_mail_QdBmyp.widget_mail
                      .widget_footer
                      .signup-newsletter-form
                      .submit-btn:hover {
                      background: #1a1a1a;
                      border-color: #1a1a1a;
                    }
                  </style>
                  <div class="footer-iteam col-lg-12 col-md-12 col-12 h_center v_ text_center">
                    <aside id="block_menu_iKNmTF" class="widget widget_nav_menu">
                      <div class="menu_footer widget_footer">
                        <ul class="menu">
                          <li class="menu-item">
                            <a href="/privacy">Privacy & Policy
                            </a>
                          </li>
                          <!-- <li class="menu-item">
                            <a href="">Shipping Policy
                            </a>
                          </li>
                          <li class="menu-item">
                            <a href="">Refund Policy
                            </a>
                          </li>
                          <li class="menu-item">
                            <a href="">Terms of Service
                            </a>
                          </li>
                          <li class="menu-item">
                            <a href="">Contact Us </a>
                          </li> -->
                        </ul>
                      </div>
                    </aside>
                  </div>
                  <div class="footer-iteam col-lg-12 col-md-12 col-12 flex h_center text_center">
                    <aside id="block_html_RdrcRa" class="widget widget_text">
                      <div class="textwidget widget_footer">
                        <div class="contentHtml">
                          <p style="
                              color: white;
                              font-size: 12px !important;
                              line-height: 18px;
                            ">
                            ©<?=date('Y')?> <?=$site_name?> All rights reserved.
                            <a target="_blank" href="" style="
                                font-size: 12px;
                                line-height: 18px;
                                font-weight: bold;
                              "></a>
                          </p>
                        </div>
                      </div>
                    </aside>
                  </div>
                </div>
              </div>
            </div>
            <!--/Footer-->
          </section>
          <style data-shopify="">
            #laber_footer1 {
              padding-top: 80px;
              padding-right: 0;
              padding-bottom: 80px;
              padding-left: 0;
              background-color: #ffffff;
            }
            @media only screen and (max-width: 1024px) {
              #laber_footer1 {
                padding-top: 40px;
                padding-right: 0;
                padding-bottom: 60px;
                padding-left: 0;
              }
            }
          </style>
        </div>
      </footer>
    </div>
</body>

<!-- Vienna Modern JS Enhancements -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Header scroll effect - add glassmorphism on scroll
  const header = document.getElementById('main-site-header');
  const heroSection = document.querySelector('.video-section');
  
  if (header && heroSection) {
    const heroHeight = heroSection.offsetHeight;
    
    window.addEventListener('scroll', function() {
      if (window.scrollY > 100) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });
  }
  
  // Scroll animations for collection items
  const animatedElements = document.querySelectorAll('.cat_grid_item');
  
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.classList.add('is-visible');
          }, index * 100);
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    });
    
    animatedElements.forEach(el => {
      el.classList.add('animate-on-scroll');
      observer.observe(el);
    });
  } else {
    // Fallback for older browsers
    animatedElements.forEach(el => el.classList.add('is-visible'));
  }
  
  // Hide scroll indicator after user scrolls
  const scrollIndicator = document.querySelector('.scroll-indicator');
  if (scrollIndicator) {
    window.addEventListener('scroll', function() {
      if (window.scrollY > 200) {
        scrollIndicator.style.opacity = '0';
      } else {
        scrollIndicator.style.opacity = '1';
      }
    });
  }
});
</script>

 <style data-shopify="">
              #laber_collection_featured_grid_ztbCVH {
                padding-top: 20px;
                padding-right: 20px;
                padding-bottom: 0px;
                padding-left: 20px;
                background-color: #ffffff;
              }
            </style>
<style>
      body {
        position: relative;
      }
      #main-collection-filters div.facets-container.facets-container-drawer {
        border: 0;
        padding: 0;
        margin-bottom: 10px;
        margin-top: intial;
      }
      .footer,
      .footer a {
        color: #eaeaea !important;
      }
      .footer .main-footer .widget-title,
      .footer .main-footer h4 {
        color: white !important;
      }
      .footer .main-footer .menu_footer .menu a {
        color: #eaeaea !important;
      }
      .footer-note .menufooter .menu li a {
        color: #eaeaea !important;
      }
      .wrap_title .content {
        padding-top: 5px;
      }
      .product-collection__content {
        padding-top: 5px;
      }
      .size-chart {
        display: none !important;
      } /* Product Slider */
      .details-thumb .slider-nav.slick-slider {
        opacity: 1;
        margin: 0 -5px 0 0;
      }
      .slick-list {
        padding-top: 40px !important;
      }
      .home .slick-list {
        padding-top: 0px !important;
      }
      .details-thumb .slider-nav.slick-slider {
        opacity: 1;
        margin: 0 -5px 0 0;
        padding-top: 0px;
      } /* Product Details */
      .details-info .product-collection__options {
        margin-bottom: 0;
      }
      .details-info .inventory_qty {
        display: none;
      }
      .product-options__value--circle {
        width: 18px !important;
        height: 18px !important;
      }
      .product-options__value.border {
        border: none;
        color: #000;
        font-size: 14px;
        font-style: normal;
        font-weight: 100;
        margin-right: 22px;
      }
      #product-single .product-options__value.active {
        border: none !important;
      }
      .details-info .cms-option-item label {
        display: none;
      }
      #product-single .product-options__value.border {
        border: none;
        margin: 0;
        padding: 0;
        width: initial;
        min-width: initial;
      }
      .details-info .product_description {
        /* display: none; */
      }
      .details-info .form {
        justify-content: center;
        align-items: center;
        margin-top: 80px;
        max-height: 100vh;
        background-color: white;
        padding: 10px 30px;
        max-width: 100%;
      }
      .details-info .cms-option-item {
        padding-bottom: 0;
        border: none;
        margin-bottom: 0;
      }
      .details-info .cms-product-meta {
        display: flex;
        flex-wrap: wrap;
        border: 0;
        padding-bottom: 10px;
        margin-bottom: 5px;
      }
      .details-info .price {
        font-size: 10px;
        font-weight: 200;
        margin: 0;
        display: block;
        line-height: 1;
      }
      .details-info .product-price {
        margin-bottom: initial;
        border-bottom: 1px solid #e6ecf0;
        border: 0;
        padding-bottom: 0px;
        font-size: 18px;
        font-weight: normal;
      }
      .details-info .product-name a {
        font-size: 12px;
        line-height: initial;
        text-transform: uppercase;
      }
      .details-info .page_product_countdown {
        display: none;
      }
      #shopify-section-sticky_add_to_cart {
        display: none;
      }
      .prod_shipping-text,
      .prod_delivery-times {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 5px;
        padding-bottom: 5px;
        border-bottom: none;
        font-size: 10px;
      }
      .details-info label {
        font-size: 10px;
      }
      .details-info .group-button .add-to-cart {
        width: 50% !important;
      }
      .product_page_social,
      .product_infor,
      .compare-wishlist {
        display: none;
      }
      .laber-breadcrumb {
        display: none;
      } /* Collection Product Listing */
      .product-collection {
        flex-direction: column;
        display: flex;
        margin-bottom: 15px;
      }
      .product-item .product-collection__content .product-collection__title a {
        width: 100%;
        font-weight: 500;
        font-size: 10px;
        line-height: 13px;
        text-transform: uppercase;
      }
      .product-item
        .product-collection__content
        .frm-price-color
        .product-collection__price
        .price
        span {
        text-decoration: unset;
        font-weight: 500;
        font-size: 10px;
      }
      .product-item .product-collection__content .product-collection__reviews {
        display: none;
      }
      .product-item .product-collection__content .product-collection__title h2 {
        margin-bottom: 0px;
      }
      .product-item .product-collection__options {
        padding-top: 0px;
      }
      .product-options.product-options--type-collection {
        justify-content: space-between;
        align-items: center;
      }
      .product-item .product-collection__options .cms-option-item {
        margin-bottom: 0px;
      }
      .product-options__value.border {
        border: none;
        color: #000;
        font-size: 11px;
        font-style: normal;
        font-weight: 100;
        margin-right: 0px;
      }
      .product-options__value.active {
        opacity: 1;
        color: var(--color-main) !important;
        font-weight: 600 !important;
      }
      .product-options__value.product-options__value--text {
        min-width: initial;
        min-height: initial;
        padding: 0;
      }
      .ratio_custom_1 .laber_bg_lz {
        padding-top: 150% !important;
      }
      body {
        font-size: 10px;
      }
      .ias-noneleft {
        display: none !important;
      }
      .laber_banner {
        padding: 0;
      }
      #shopify-section-menu-mobile-bottom {
        display: none !important;
      }
      #shopify-section-products-recently-viewed {
        display: none !important;
      }
      #shopify-section-related-product-carousel {
        padding: 10px;
      }
      .tab_product_page {
        display: none;
      }
      #laber_related-product-carousel .grid-init .alo-item {
        padding: 0 !important;
        padding-bottom: 10px;
      }
      .slick-carousel .slick-list {
        padding-top: 0px !important;
      }
      #laber_related-product-carousel .container {
        width: 100% !important;
        margin-right: initial;
        margin-left: initial;
      }
      .wrap_title {
        margin-bottom: 15px;
      }
      .ratio3_4 .laber_bg_lz {
        padding-top: 150% !important;
        margin-right: 2.5px;
      }
      .wrap_title.medium .section-title {
        font-size: 17px;
        line-height: 18.5px;
        font-weight: 300;
        text-align: left;
        align-self: start;
        text-transform: uppercase;
      }
      .mobile-facets__heading {
        font-size: 15px;
        font-weight: 300;
        margin-bottom: 15px;
      }
      .mobile-facets__sort .select__select {
        border: none;
        font-size: 10px;
        text-transform: uppercase;
      }
      .home .header-top.sticky-header {
        background-color: transparent;
      }
      .home .slick-list {
        padding-top: 0 !important;
      }
      .header .header-top .header-logo {
        display: flex;
        padding: 0;
        padding-left: 7px;
      }
      #shopify-section-header1 a {
        font-weight: 300;
      }
      #main-collection-filters {
        font-weight: 300 !important;
        line-height: 1.4em !important;
        text-transform: uppercase !important;
      }
      .title--primary,
      .facet-filters__label {
        font-size: 10px;
        font-weight: 300;
        line-height: 1.4em;
        text-transform: uppercase;
      }
      label {
        font-weight: 300;
      }
      .product-count__text {
        font-size: 10px;
      }
      .facet-filters__sort {
        font-size: 10px;
        text-transform: uppercase;
      }
      .collection--empty .title--primary {
        font-size: 14px;
      }
      .header-nav-inner {
        display: none;
      }
      .main-footer {
        padding-bottom: 20px;
      }
      .logo-footer img {
        filter: brightness(0) invert(1);
        max-width: 70px !important;
      }
      .main-footer .txt_title {
        font-weight: 300;
        text-transform: uppercase;
      }
      .footer .main-footer .menu_footer .menu li a {
        line-height: 11.5px;
        text-transform: uppercase;
        font-size: 10px;
      }
      .footer .main-footer .menu_footer .menu li {
        line-height: 16.5px !important;
      }
      .footer .line {
        display: none !important;
      }
      .footer-note {
        padding: 10px 0px !important;
      }
      .footer-note,
      .footer-note p {
        text-transform: uppercase;
        font-size: 10px !important;
        line-height: 16.5px !important;
      }
      .footer-note .menufooter .menu li a {
        line-height: 16.5px;
        text-transform: uppercase;
        font-size: 10px;
      }
      .footer-note .menufooter .menu {
        gap: 10px;
      }
      .footer-note .menufooter {
        order: 2;
        display: flex;
        justify-content: flex-end;
        padding: 0;
        margin: 0;
        margin-bottom: 0 !important;
      }
      .footer .widget_footer .signup-newsletter-form .input-text {
        color: white;
      }
      .shopify-policy__container {
        max-width: 80% !important;
      }
      .js_popup_prpr_wrap_newsletter .title_newslette h3 {
        font-weight: 300 !important;
        text-transform: uppercase;
      }
      @media (max-width: 768px) {
        .details-info .form {
          justify-content: center;
          align-items: center;
          margin-top: 0;
          max-height: 100vh;
          background-color: white;
          padding: 10px 15px;
        }
        .details-info .group-button .add-to-cart {
          width: 100% !important;
        }
        .slick-list {
          padding-top: 0 !important;
        }
        .slick-dots {
          display: none !important;
        }
        .details-thumb {
          margin-bottom: 10px;
        }
        .home .slick-list {
          padding-top: 0 !important;
        }
        .shopify-policy__container {
          max-width: 100% !important;
        }
      } /* Product Accordion */
      .product__accordion {
        margin-bottom: 0rem;
        overflow: hidden;
      }
      .product__accordion details {
        display: block;
      }
      .product__accordion summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        cursor: pointer;
        font-size: 1rem;
        font-weight: bold;
        color: #333;
        border: none;
        transition: background-color 0.3s ease, color 0.3s ease;
        border-bottom: 0.5px solid #000;
      }
      .product__accordion summary:hover {
      }
      .product__accordion details[open] summary {
        padding: 0.5rem 0;
      }
      .accordion__title {
        margin: 0;
        font-weight: 300;
        text-transform: uppercase;
        font-size: 12px;
        text-align: left !important;
      }
      .accordion__content .product__description {
        padding: 0.4rem 0px;
      }
      .product__accordion summary svg {
        width: 24px;
        height: 24px;
        flex-shrink: 0;
        margin-left: 1rem;
        fill: white;
        transition: transform 0.3s ease, fill 0.3s ease;
        background: black;
        border-radius: 24px;
        padding: 5px;
        color: white;
      }
      .product__accordion details[open] summary svg {
        transform: rotate(180deg);
        fill: #0078d7; /* Accent color for open state */
      } /* Accordion content */
      .accordion__content {
        padding: 0;
        display: none;
        animation: fadeIn 0.3s ease-in-out;
        font-size: 12px;
      }
      .product__accordion details[open] .accordion__content {
        display: block;
      }
      @media (max-width: 380px) {
        .accordion__title {
          text-align: left !important;
        }
      } /* Animation for smooth content reveal */
      @keyframes fadeIn {
        from {
          opacity: 0;
          transform: translateY(-5px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      .aboutus-container {
        margin-top: 20px;
      }
      .aboutus-container,
      .aboutus-container p {
        font-size: 15px !important;
      }
      .content-form .contact-info h3,
      .information-form .main-title {
        font-weight: 300 !important;
      }
      #laber_footer1 {
        background-color: #000000;
      }
      .main-footer a {
        color: white !important;
        font-size: 32px;
        margin-bottom: 5px;
      }
      .menu_footer ul {
        margin-top: 15px;
      }
      .menu_footer ul li {
        display: inline-block;
        padding: 0 10px;
      }
      .menu_footer ul li a {
        display: inline-block;
        font-size: 16px;
        font-weight: 300;
        text-transform: uppercase;
      }
      .laber_banner {
        padding: 0px !important;
      }
      .product-item .product-collection__options .cms-option-item {
        margin-bottom: 0px;
        text-transform: uppercase;
        letter-spacing: normal;
      }
      .hero_canvas .js-cart-inner .list-item .product-item .info .product-name {
        font-weight: 300;
      }
      @media only screen and (max-width: 767px) {
        #laber_banner_full_width_6eNBUi .laber_bg_lz {
          padding-top: 440px !important;
        }
      }
      .slick-track {
        align-items: start !important;
      }
      #footer {
        border: 0 !important;
      }
      #footer .newsletter-content h3 {
        color: white;
        margin-top: 30px;
        margin-bottom: 9px;
        font-size: 32px;
      }
      .home .logo-slogan img {
        /* filter: brightness(1) invert(0) !important; */
      }
      .home .logo-slogan span {
        color: #fff;
      }
      .essential-preorder-container-active span {
        justify-content: !important inherit;
      }
      #product-single .product-options__value.border {
        text-transform: uppercase;
      }
      .strike {
        text-decoration: line-through;
      }
      .brandingStyle {
        display: none !important;
      }
      .notifyButtonStyle {
        width: 50%;
        display: flex;
        background-color: var(--color-btn);
        color: var(--color-btn-text);
        border-radius: 3px;
        border: none;
        padding: 0 30px;
        line-height: 50px;
        text-align: center;
        text-transform: uppercase;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        position: relative;
        align-items: center;
        justify-content: center;
      }
</style>