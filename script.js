// Hero Slider Variables
let currentSlideIndex = 0
const slides = document.querySelectorAll(".hero-slide")
const dots = document.querySelectorAll(".dot")
let slideInterval

// Initialize slider when page loads
document.addEventListener("DOMContentLoaded", () => {
  if (slides.length > 0) {
    startSlider()
  }

  // Add smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault()
      const target = document.querySelector(this.getAttribute("href"))
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        })
      }
    })
  })

  // Update cart badge on page load
  updateCartBadge()

  // Add CSS animations
  const style = document.createElement("style")
  style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.2); }
      100% { transform: scale(1); }
    }
    
    .payment-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 2000;
    }
    
    .payment-modal-content {
      background: white;
      padding: 2rem;
      border-radius: 10px;
      max-width: 500px;
      width: 90%;
      max-height: 80vh;
      overflow-y: auto;
    }
    
    .simulation-steps {
      margin: 2rem 0;
    }
    
    .step {
      display: flex;
      align-items: center;
      padding: 0.5rem 0;
      border-bottom: 1px solid #eee;
    }
    
    .step-number {
      background: #e74c3c;
      color: white;
      border-radius: 50%;
      width: 25px;
      height: 25px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 1rem;
      font-size: 0.8rem;
    }
    
    .step.completed .step-number {
      background: #27ae60;
    }
    
    .step-text {
      flex: 1;
    }
    
    .step-status {
      font-size: 1.2rem;
    }
    
    .simulation-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      margin-top: 2rem;
    }
    
    .payment-method {
      border: 2px solid #ddd;
      padding: 1rem;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s;
      margin-bottom: 1rem;
    }
    
    .payment-method:hover {
      border-color: #e74c3c;
    }
    
    .payment-method.selected {
      border-color: #e74c3c;
      background-color: #fef5f5;
    }
    
    .payment-method h4 {
      margin: 0 0 0.5rem 0;
      color: #2c3e50;
    }
    
    .payment-method p {
      margin: 0;
      color: #666;
      font-size: 0.9rem;
    }
  `
  document.head.appendChild(style)

  // Add form validation to registration form
  const registerForm = document.getElementById("registerForm")
  if (registerForm) {
    registerForm.addEventListener("submit", (e) => {
      const email = document.getElementById("email").value
      const password = document.getElementById("password").value

      if (!validateEmail(email)) {
        e.preventDefault()
        showNotification("Please enter a valid email address", "error")
        return
      }

      const passwordError = checkPasswordStrength(password)
      if (passwordError) {
        e.preventDefault()
        showNotification(passwordError, "error")
        return
      }
    })
  }
})

// Start automatic slider
function startSlider() {
  slideInterval = setInterval(() => {
    changeSlide(1)
  }, 5000) // Change slide every 5 seconds
}

// Stop automatic slider
function stopSlider() {
  clearInterval(slideInterval)
}

// Change slide function
function changeSlide(direction) {
  // Remove active class from current slide and dot
  slides[currentSlideIndex].classList.remove("active")
  dots[currentSlideIndex].classList.remove("active")

  // Calculate new slide index
  currentSlideIndex += direction

  // Handle wrap around
  if (currentSlideIndex >= slides.length) {
    currentSlideIndex = 0
  } else if (currentSlideIndex < 0) {
    currentSlideIndex = slides.length - 1
  }

  // Add active class to new slide and dot
  slides[currentSlideIndex].classList.add("active")
  dots[currentSlideIndex].classList.add("active")
}

// Go to specific slide
function currentSlide(slideIndex) {
  // Stop automatic slider temporarily
  stopSlider()

  // Remove active class from current slide and dot
  slides[currentSlideIndex].classList.remove("active")
  dots[currentSlideIndex].classList.remove("active")

  // Set new slide index
  currentSlideIndex = slideIndex - 1

  // Add active class to new slide and dot
  slides[currentSlideIndex].classList.add("active")
  dots[currentSlideIndex].classList.add("active")

  // Restart automatic slider after 10 seconds
  setTimeout(startSlider, 10000)
}

// Pause slider on hover
document.querySelector(".hero-slider")?.addEventListener("mouseenter", stopSlider)
document.querySelector(".hero-slider")?.addEventListener("mouseleave", startSlider)

// Add to cart functionality with improved error handling
function addToCart(productId) {
  // Show loading state
  const button = event.target
  const originalText = button.textContent
  button.textContent = "Adding..."
  button.disabled = true

  fetch("add_to_cart.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "product_id=" + productId,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok")
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        // Show success message
        showNotification(`${data.product_name} added to cart!`, "success")

        // Update cart badge
        updateCartBadge(data.cart_count)

        // Reset button
        button.textContent = "Added!"
        button.style.backgroundColor = "#27ae60"

        setTimeout(() => {
          button.textContent = originalText
          button.style.backgroundColor = ""
          button.disabled = false
        }, 2000)
      } else {
        showNotification("Error: " + data.message, "error")
        button.textContent = originalText
        button.disabled = false
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("Network error occurred. Please try again.", "error")
      button.textContent = originalText
      button.disabled = false
    })
}

// Show notification function
function showNotification(message, type = "info") {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll(".notification")
  existingNotifications.forEach((notification) => notification.remove())

  // Create notification element
  const notification = document.createElement("div")
  notification.className = `notification ${type}`
  notification.textContent = message

  // Style the notification
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 5px;
    color: white;
    font-weight: bold;
    z-index: 1000;
    max-width: 300px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    animation: slideIn 0.3s ease-out;
  `

  // Set background color based on type
  switch (type) {
    case "success":
      notification.style.backgroundColor = "#27ae60"
      break
    case "error":
      notification.style.backgroundColor = "#e74c3c"
      break
    default:
      notification.style.backgroundColor = "#3498db"
  }

  // Add to page
  document.body.appendChild(notification)

  // Auto remove after 3 seconds
  setTimeout(() => {
    notification.style.animation = "slideOut 0.3s ease-in"
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification)
      }
    }, 300)
  }, 3000)
}

// Update cart badge with animation
function updateCartBadge(count = null) {
  if (count !== null) {
    const badge = document.querySelector(".cart-badge")
    if (badge) {
      badge.textContent = count
      badge.style.animation = "pulse 0.3s ease-in-out"
      setTimeout(() => {
        badge.style.animation = ""
      }, 300)
    }
    return
  }

  // Fetch current count if not provided
  fetch("get_cart_count.php")
    .then((response) => response.json())
    .then((data) => {
      const badge = document.querySelector(".cart-badge")
      if (badge) {
        badge.textContent = data.count
      }
    })
    .catch((error) => {
      console.error("Error updating cart badge:", error)
    })
}

// Update cart quantity
function updateQuantity(cartId, change) {
  fetch("update_cart.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "cart_id=" + cartId + "&change=" + change,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        location.reload()
      } else {
        showNotification("Error updating cart: " + data.message, "error")
      }
    })
    .catch((error) => {
      console.error("Error:", error)
      showNotification("Network error occurred", "error")
    })
}

// Remove from cart
function removeFromCart(cartId) {
  if (confirm("Are you sure you want to remove this item from cart?")) {
    fetch("remove_from_cart.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "cart_id=" + cartId,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showNotification("Item removed from cart", "success")
          setTimeout(() => location.reload(), 1000)
        } else {
          showNotification("Error removing item: " + data.message, "error")
        }
      })
      .catch((error) => {
        console.error("Error:", error)
        showNotification("Network error occurred", "error")
      })
  }
}

// Payment simulation functions
function selectPaymentMethod(method) {
  const methods = document.querySelectorAll(".payment-method")
  methods.forEach((m) => m.classList.remove("selected"))

  const selectedMethod = document.querySelector(`[data-method="${method}"]`)
  if (selectedMethod) {
    selectedMethod.classList.add("selected")
  }

  document.getElementById("selected_payment_method").value = method

  // Show payment instructions
  showPaymentInstructions(method)
}

function showPaymentInstructions(method) {
  const instructionsDiv = document.getElementById("payment-instructions")
  const totalAmount = document.getElementById("total-amount").textContent

  let instructions = ""

  switch (method) {
    case "M-Pesa":
      instructions = `
        <h4>M-Pesa Payment Instructions:</h4>
        <ol>
          <li>Dial <strong>*150*00#</strong> on your phone</li>
          <li>Select "Send Money"</li>
          <li>Enter Business Number: <strong>123456</strong></li>
          <li>Enter Amount: <strong>${totalAmount}</strong></li>
          <li>Enter PIN and confirm</li>
          <li>You will receive a confirmation SMS</li>
        </ol>
        <p><strong>Reference:</strong> BREATHE${Date.now()}</p>
      `
      break

    case "Airtel Money":
      instructions = `
        <h4>Airtel Money Payment Instructions:</h4>
        <ol>
          <li>Dial <strong>*150*60#</strong> on your phone</li>
          <li>Select "Send Money"</li>
          <li>Enter Merchant Code: <strong>654321</strong></li>
          <li>Enter Amount: <strong>${totalAmount}</strong></li>
          <li>Enter PIN and confirm</li>
          <li>You will receive a confirmation SMS</li>
        </ol>
        <p><strong>Reference:</strong> BREATHE${Date.now()}</p>
      `
      break

    case "Bank Transfer":
      instructions = `
        <h4>Bank Transfer Instructions:</h4>
        <p><strong>Bank:</strong> CRDB Bank</p>
        <p><strong>Account Name:</strong> Breathe Luxe Ltd</p>
        <p><strong>Account Number:</strong> 0150-123456-00</p>
        <p><strong>Amount:</strong> ${totalAmount}</p>
        <p><strong>Reference:</strong> BREATHE${Date.now()}</p>
        <p>Please use the reference number when making the transfer.</p>
      `
      break
  }

  instructionsDiv.innerHTML = instructions
  instructionsDiv.style.display = "block"
}

function simulatePayment() {
  const paymentMethod = document.getElementById("selected_payment_method").value

  if (!paymentMethod) {
    showNotification("Please select a payment method", "error")
    return
  }

  // Show payment simulation modal
  showPaymentSimulation(paymentMethod)
}

function showPaymentSimulation(method) {
  const modal = document.createElement("div")
  modal.className = "payment-modal"
  modal.innerHTML = `
    <div class="payment-modal-content">
      <h3>Simulate ${method} Payment</h3>
      <p>This is a simulation. In a real scenario, you would complete the payment using your mobile phone.</p>
      
      <div class="simulation-steps">
        <div class="step" id="step1">
          <span class="step-number">1</span>
          <span class="step-text">Initiating payment...</span>
          <span class="step-status">⏳</span>
        </div>
        <div class="step" id="step2">
          <span class="step-number">2</span>
          <span class="step-text">Connecting to ${method}...</span>
          <span class="step-status">⏳</span>
        </div>
        <div class="step" id="step3">
          <span class="step-number">3</span>
          <span class="step-text">Processing payment...</span>
          <span class="step-status">⏳</span>
        </div>
        <div class="step" id="step4">
          <span class="step-number">4</span>
          <span class="step-text">Payment confirmation...</span>
          <span class="step-status">⏳</span>
        </div>
      </div>
      
      <div class="simulation-buttons">
        <button onclick="completePaymentSimulation(true)" class="btn btn-success">Simulate Success</button>
        <button onclick="completePaymentSimulation(false)" class="btn btn-secondary">Simulate Failure</button>
        <button onclick="closePaymentModal()" class="btn btn-secondary">Cancel</button>
      </div>
    </div>
  `

  document.body.appendChild(modal)

  // Start simulation animation
  setTimeout(() => updateStep("step1", "✅"), 1000)
  setTimeout(() => updateStep("step2", "✅"), 2000)
  setTimeout(() => updateStep("step3", "✅"), 3000)
  setTimeout(() => updateStep("step4", "✅"), 4000)
}

function updateStep(stepId, status) {
  const step = document.getElementById(stepId)
  if (step) {
    step.querySelector(".step-status").textContent = status
    step.classList.add("completed")
  }
}

function completePaymentSimulation(success) {
  if (success) {
    // Mark payment as completed and submit form
    document.getElementById("payment_completed").checked = true
    showNotification("Payment simulation successful!", "success")
    closePaymentModal()

    // Auto-submit the form after a short delay
    setTimeout(() => {
      document.getElementById("checkout-form").submit()
    }, 1500)
  } else {
    showNotification("Payment simulation failed. Please try again.", "error")
    closePaymentModal()
  }
}

function closePaymentModal() {
  const modal = document.querySelector(".payment-modal")
  if (modal) {
    modal.remove()
  }
}

// Form validation
function validateForm(formId) {
  const form = document.getElementById(formId)
  const inputs = form.querySelectorAll("input[required]")
  let isValid = true

  inputs.forEach((input) => {
    if (!input.value.trim()) {
      input.style.borderColor = "#e74c3c"
      isValid = false
    } else {
      input.style.borderColor = "#ddd"
    }
  })

  return isValid
}

// Email validation
function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}

// Password strength check
function checkPasswordStrength(password) {
  const minLength = 6
  const hasUpperCase = /[A-Z]/.test(password)
  const hasLowerCase = /[a-z]/.test(password)
  const hasNumbers = /\d/.test(password)

  if (password.length < minLength) {
    return "Password must be at least 6 characters long"
  }
  if (!hasUpperCase || !hasLowerCase || !hasNumbers) {
    return "Password must contain uppercase, lowercase, and numbers"
  }
  return ""
}
