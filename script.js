document.addEventListener("DOMContentLoaded", () => {
  // Mobile Menu Toggle
  const menuToggle = document.querySelector(".menu-toggle")
  const nav = document.querySelector("nav")

  if (menuToggle) {
    menuToggle.addEventListener("click", () => {
      menuToggle.classList.toggle("active")
      nav.classList.toggle("active")
    })
  }

  // FAQ Accordion
  const faqItems = document.querySelectorAll(".faq-item")

  faqItems.forEach((item) => {
    const question = item.querySelector(".faq-question")

    if (question) {
      question.addEventListener("click", () => {
        item.classList.toggle("active")
      })
    }
  })

  // Booking Form Validation
  const bookingForm = document.getElementById("bookingForm")

  if (bookingForm) {
    // Show/hide M-Pesa number field based on payment method
    const mpesaRadio = document.getElementById("mpesa")
    const cashRadio = document.getElementById("cash")
    const mpesaDetails = document.getElementById("mpesaDetails")

    if (mpesaRadio && cashRadio && mpesaDetails) {
      mpesaRadio.addEventListener("change", function () {
        if (this.checked) {
          mpesaDetails.classList.remove("hidden")
        }
      })

      cashRadio.addEventListener("change", function () {
        if (this.checked) {
          mpesaDetails.classList.add("hidden")
        }
      })
    }

    // Form validation
    bookingForm.addEventListener("submit", (e) => {
      e.preventDefault()

      let isValid = true

      // Validate full name
      const fullName = document.getElementById("fullName")
      const fullNameError = document.getElementById("fullNameError")

      if (fullName && fullNameError) {
        if (fullName.value.trim() === "") {
          fullNameError.textContent = "Please enter your full name"
          isValid = false
        } else {
          fullNameError.textContent = ""
        }
      }

      // Validate email
      const email = document.getElementById("email")
      const emailError = document.getElementById("emailError")

      if (email && emailError) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

        if (email.value.trim() === "") {
          emailError.textContent = "Please enter your email address"
          isValid = false
        } else if (!emailRegex.test(email.value.trim())) {
          emailError.textContent = "Please enter a valid email address"
          isValid = false
        } else {
          emailError.textContent = ""
        }
      }

      // Validate phone
      const phone = document.getElementById("phone")
      const phoneError = document.getElementById("phoneError")

      if (phone && phoneError) {
        if (phone.value.trim() === "") {
          phoneError.textContent = "Please enter your phone number"
          isValid = false
        } else {
          phoneError.textContent = ""
        }
      }

      // Validate package
      const packageSelect = document.getElementById("package")
      const packageError = document.getElementById("packageError")

      if (packageSelect && packageError) {
        if (packageSelect.value === "") {
          packageError.textContent = "Please select a package"
          isValid = false
        } else {
          packageError.textContent = ""
        }
      }

      // Validate date
      const date = document.getElementById("date")
      const dateError = document.getElementById("dateError")

      if (date && dateError) {
        if (date.value === "") {
          dateError.textContent = "Please select a date"
          isValid = false
        } else {
          dateError.textContent = ""
        }
      }

      // Validate time
      const time = document.getElementById("time")
      const timeError = document.getElementById("timeError")

      if (time && timeError) {
        if (time.value === "") {
          timeError.textContent = "Please select a time"
          isValid = false
        } else {
          timeError.textContent = ""
        }
      }

      // Validate address
      const address = document.getElementById("address")
      const addressError = document.getElementById("addressError")

      if (address && addressError) {
        if (address.value.trim() === "") {
          addressError.textContent = "Please enter the event address"
          isValid = false
        } else {
          addressError.textContent = ""
        }
      }

      // Validate payment method
      const paymentMethodError = document.getElementById("paymentMethodError")

      if (mpesaRadio && cashRadio && paymentMethodError) {
        if (!mpesaRadio.checked && !cashRadio.checked) {
          paymentMethodError.textContent = "Please select a payment method"
          isValid = false
        } else {
          paymentMethodError.textContent = ""
        }
      }

      // Validate M-Pesa number if M-Pesa is selected
      const mpesaNumber = document.getElementById("mpesaNumber")
      const mpesaNumberError = document.getElementById("mpesaNumberError")

      if (mpesaRadio && mpesaRadio.checked && mpesaNumber && mpesaNumberError) {
        if (mpesaNumber.value.trim() === "") {
          mpesaNumberError.textContent = "Please enter your M-Pesa number"
          isValid = false
        } else {
          mpesaNumberError.textContent = ""
        }
      }

      // Validate terms
      const terms = document.getElementById("terms")
      const termsError = document.getElementById("termsError")

      if (terms && termsError) {
        if (!terms.checked) {
          termsError.textContent = "You must agree to the terms and conditions"
          isValid = false
        } else {
          termsError.textContent = ""
        }
      }

      // If form is valid, submit it
      if (isValid) {
        // For demonstration purposes, we'll show the confirmation message
        // In a real application, this would be handled by the server
        const bookingConfirmation = document.getElementById("bookingConfirmation")
        const bookingContainer = document.querySelector(".booking-container")
        const bookingReference = document.getElementById("bookingReference")

        if (bookingConfirmation && bookingContainer && bookingReference) {
          // Generate a random booking reference
          const reference =
            "BL" +
            Math.floor(Math.random() * 10000)
              .toString()
              .padStart(4, "0")
          bookingReference.textContent = reference

          // Hide the form and show the confirmation
          bookingContainer.classList.add("hidden")
          bookingConfirmation.classList.remove("hidden")

          // In a real application, we would submit the form to the server
          // bookingForm.submit();
        }
      }
    })
  }

  // Contact Form Validation
  const contactForm = document.getElementById("contactForm")

  if (contactForm) {
    contactForm.addEventListener("submit", (e) => {
      e.preventDefault()

      let isValid = true

      // Validate name
      const contactName = document.getElementById("contactName")
      const contactNameError = document.getElementById("contactNameError")

      if (contactName && contactNameError) {
        if (contactName.value.trim() === "") {
          contactNameError.textContent = "Please enter your name"
          isValid = false
        } else {
          contactNameError.textContent = ""
        }
      }

      // Validate email
      const contactEmail = document.getElementById("contactEmail")
      const contactEmailError = document.getElementById("contactEmailError")

      if (contactEmail && contactEmailError) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

        if (contactEmail.value.trim() === "") {
          contactEmailError.textContent = "Please enter your email address"
          isValid = false
        } else if (!emailRegex.test(contactEmail.value.trim())) {
          contactEmailError.textContent = "Please enter a valid email address"
          isValid = false
        } else {
          contactEmailError.textContent = ""
        }
      }

      // Validate subject
      const contactSubject = document.getElementById("contactSubject")
      const contactSubjectError = document.getElementById("contactSubjectError")

      if (contactSubject && contactSubjectError) {
        if (contactSubject.value.trim() === "") {
          contactSubjectError.textContent = "Please enter a subject"
          isValid = false
        } else {
          contactSubjectError.textContent = ""
        }
      }

      // Validate message
      const contactMessage = document.getElementById("contactMessage")
      const contactMessageError = document.getElementById("contactMessageError")

      if (contactMessage && contactMessageError) {
        if (contactMessage.value.trim() === "") {
          contactMessageError.textContent = "Please enter your message"
          isValid = false
        } else {
          contactMessageError.textContent = ""
        }
      }

      // If form is valid, submit it
      if (isValid) {
        // For demonstration purposes, we'll show the confirmation message
        // In a real application, this would be handled by the server
        const contactConfirmation = document.getElementById("contactConfirmation")
        const contactContainer = document.querySelector(".contact-container")

        if (contactConfirmation && contactContainer) {
          // Hide the form and show the confirmation
          contactContainer.classList.add("hidden")
          contactConfirmation.classList.remove("hidden")

          // In a real application, we would submit the form to the server
          // contactForm.submit();
        }
      }
    })
  }

  // Pre-select package from URL parameter
  const urlParams = new URLSearchParams(window.location.search)
  const packageParam = urlParams.get("package")

  if (packageParam) {
    const packageSelect = document.getElementById("package")

    if (packageSelect) {
      packageSelect.value = packageParam
    }
  }
})
