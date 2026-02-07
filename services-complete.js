// ✅ COMPLETE SERVICE DEFINITIONS WITH UNIQUE PLACEHOLDERS
const services = {
  // === METROPOL IDENTITY SERVICES ===
  'id-verification': {
    title: "National ID <span>Verification</span>",
    desc: "Instant verification of Kenyan National ID to confirm validity and authenticity.",
    infoTitle: "What this National ID check includes",
    infoList: [
      "Identity number validity with IPRS database",
      "Document authenticity confirmation",
      "Basic identity match status",
      "Safe verification with no credit score impact"
    ],
    inputLabel: "National ID Number",
    inputPlaceholder: "Enter National ID (e.g., 12345678)",
    inputHelp: "Format: 7-9 digits only",
    inputIcon: "fa-id-card",
    formNote: "Your National ID is used only for verification. Full privacy guaranteed.",
    buttonText: "Verify National ID",
    provider: "metropol"
  },
  
  // === YOUVERIFY KENYA SERVICES ===
  'yv-ke-id': {
    title: "Kenya National ID <span>Verification</span>",
    desc: "Verify Kenyan National ID with IPRS database, photo match and biometric validation.",
    infoTitle: "What this National ID verification includes",
    infoList: [
      "ID number validation with IPRS",
      "Photo match verification",
      "Biometric data validation",
      "Real-time verification status"
    ],
    inputLabel: "National ID Number",
    inputPlaceholder: "Enter National ID (e.g., 12345678)",
    inputHelp: "Format: 7-9 digits",
    inputIcon: "fa-id-card",
    formNote: "Enhanced verification with photo matching and biometric validation.",
    buttonText: "Verify National ID",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-ke-passport': {
    title: "Kenya Passport <span>Verification</span>",
    desc: "Verify Kenyan International Passport authenticity and validity with official database.",
    infoTitle: "What this Passport verification includes",
    infoList: [
      "Passport number validation with official database",
      "Document authenticity check",
      "Holder information verification (optional)",
      "Status confirmation: found / not found"
    ],
    inputLabel: "Passport Number",
    inputPlaceholder: "Enter Passport Number (e.g., AK0167656)",
    inputHelp: "Format: 1-2 letters + 7 digits (e.g., AK0167656, A2081731)",
    inputPattern: "^[A-Z]{1,2}\\d{7}$",
    inputMaxLength: "9",
    inputIcon: "fa-passport",
    formNote: "Passport number required. Names optional for enhanced matching.",
    buttonText: "Verify Passport",
    provider: "youverify",
    requiresName: false
  },
  
  'yv-ke-alien-id': {
    title: "Kenya Alien ID <span>Verification</span>",
    desc: "Verify Alien/Foreigner ID registered in Kenya with immigration database.",
    infoTitle: "What this Alien ID verification includes",
    infoList: [
      "Alien ID validation with immigration database",
      "Immigration records verification",
      "Personal details confirmation",
      "Residence status check"
    ],
    inputLabel: "Alien ID Number",
    inputPlaceholder: "Enter Alien ID (e.g., 123456789)",
    inputHelp: "Format: 7-15 digits",
    inputIcon: "fa-id-card",
    formNote: "Verify foreign nationals registered in Kenya.",
    buttonText: "Verify Alien ID",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-ke-drivers-license': {
    title: "Kenya Drivers License <span>Verification</span>",
    desc: "Verify Kenyan driving license validity and authenticity with NTSA database.",
    infoTitle: "What this Drivers License verification includes",
    infoList: [
      "License number validation with NTSA",
      "Driver personal information verification",
      "License status and validity check",
      "License class and endorsements"
    ],
    inputLabel: "Drivers License Number",
    inputPlaceholder: "Enter License Number (e.g., DL12345678)",
    inputHelp: "Format: 8-15 alphanumeric characters",
    inputIcon: "fa-id-card-clip",
    formNote: "Verify driving license with National Transport and Safety Authority.",
    buttonText: "Verify Drivers License",
    provider: "youverify",
    requiresName: true,
    requiresDOB: true
  },
  
  'yv-ke-bank-account': {
    title: "Kenya Bank Account <span>Verification</span>",
    desc: "Verify Kenyan bank account details and ownership with banking institutions.",
    infoTitle: "What this Bank Account verification includes",
    infoList: [
      "Account number validation",
      "Bank institution verification",
      "Account holder name confirmation",
      "Account status check (active/closed)"
    ],
    inputLabel: "Bank Account Number",
    inputPlaceholder: "Enter Account Number (e.g., 1234567890)",
    inputHelp: "Format: 10-15 digits",
    inputIcon: "fa-building-columns",
    formNote: "Verify bank account details with Kenyan banks.",
    buttonText: "Verify Bank Account",
    provider: "youverify",
    requiresBankCode: true
  },
  
  'yv-ke-tax': {
    title: "KRA PIN <span>Verification</span>",
    desc: "Verify Kenya Revenue Authority PIN number and tax compliance status.",
    infoTitle: "What this KRA PIN verification includes",
    infoList: [
      "KRA PIN validation with Kenya Revenue Authority",
      "Tax compliance status check",
      "Taxpayer registration details",
      "PIN holder name verification"
    ],
    inputLabel: "KRA PIN Number",
    inputPlaceholder: "Enter KRA PIN (e.g., A012345678X)",
    inputHelp: "Format: Letter + 9 digits + Letter (e.g., A012345678X)",
    inputIcon: "fa-receipt",
    formNote: "Verify tax PIN with Kenya Revenue Authority.",
    buttonText: "Verify KRA PIN",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-ke-phone': {
    title: "Kenya Phone <span>Verification</span>",
    desc: "Verify Kenyan phone number ownership and registration with telecom providers.",
    infoTitle: "What this Phone verification includes",
    infoList: [
      "Phone number validation",
      "SIM card registration verification",
      "Ownership confirmation",
      "Network carrier information"
    ],
    inputLabel: "National ID Number",
    inputPlaceholder: "Enter National ID (e.g., 12345678)",
    inputHelp: "Format: 7-9 digits (phone number entered separately)",
    inputIcon: "fa-mobile-screen",
    formNote: "Verify phone number ownership with telecom providers.",
    buttonText: "Verify Phone Number",
    provider: "youverify",
    requiresPhone: true
  },
  
  'yv-ke-plate-number': {
    title: "Kenya Vehicle Plate <span>Verification</span>",
    desc: "Verify vehicle registration details with NTSA database using plate number.",
    infoTitle: "What this Vehicle verification includes",
    infoList: [
      "Plate number validation with NTSA",
      "Vehicle registration details",
      "Owner information confirmation",
      "Vehicle make, model, and year"
    ],
    inputLabel: "Vehicle Plate Number",
    inputPlaceholder: "Enter Plate Number (e.g., KAA123X)",
    inputHelp: "Format: 3 letters + 3 digits + 1 letter (e.g., KAA123X)",
    inputIcon: "fa-car",
    formNote: "Verify vehicle details with NTSA database.",
    buttonText: "Verify Vehicle Plate",
    provider: "youverify"
  },
  
  // === NIGERIA SERVICES ===
  'yv-ng-bvn': {
    title: "Nigeria BVN <span>Verification</span>",
    desc: "Verify Nigerian Bank Verification Number with NIBSS database.",
    infoTitle: "What this BVN verification includes",
    infoList: [
      "BVN validation with NIBSS database",
      "Personal details confirmation",
      "Photo match verification",
      "Linked bank accounts information"
    ],
    inputLabel: "BVN Number",
    inputPlaceholder: "Enter BVN (e.g., 12345678901)",
    inputHelp: "Format: 11 digits",
    inputIcon: "fa-building-columns",
    formNote: "Verify Bank Verification Number with Nigerian banking system.",
    buttonText: "Verify BVN",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-ng-nin': {
    title: "Nigeria NIN <span>Verification</span>",
    desc: "Verify Nigerian National Identification Number with NIMC database.",
    infoTitle: "What this NIN verification includes",
    infoList: [
      "NIN validation with NIMC",
      "Personal details verification",
      "Biometric data check",
      "Identity document status"
    ],
    inputLabel: "NIN Number",
    inputPlaceholder: "Enter NIN (e.g., 12345678901)",
    inputHelp: "Format: 11 digits",
    inputIcon: "fa-id-card",
    formNote: "Verify National Identification Number with NIMC.",
    buttonText: "Verify NIN",
    provider: "youverify",
    requiresName: true
  },
  
  // === GHANA SERVICES ===
  'yv-gh-drivers-license': {
    title: "Ghana Drivers License <span>Verification</span>",
    desc: "Verify Ghana driving license with DVLA database.",
    infoTitle: "What this License verification includes",
    infoList: [
      "License validation with DVLA",
      "Driver information verification",
      "License class and validity",
      "Endorsements and restrictions"
    ],
    inputLabel: "Ghana License Number",
    inputPlaceholder: "Enter License Number (e.g., G1234567)",
    inputHelp: "Format: 6-12 alphanumeric characters",
    inputIcon: "fa-id-card-clip",
    formNote: "Verify driving license with Ghana DVLA.",
    buttonText: "Verify Ghana License",
    provider: "youverify",
    requiresName: true
  },
  
  'yv-gh-voters-id': {
    title: "Ghana Voters ID <span>Verification</span>",
    desc: "Verify Ghana Electoral Commission voters ID card.",
    infoTitle: "What this Voters ID verification includes",
    infoList: [
      "Voters ID validation with Electoral Commission",
      "Voter registration details",
      "Polling station information",
      "Registration status confirmation"
    ],
    inputLabel: "Voters ID Number",
    inputPlaceholder: "Enter Voters ID (e.g., 1234567890)",
    inputHelp: "Format: 10 digits",
    inputIcon: "fa-id-card",
    formNote: "Verify voters registration with Ghana Electoral Commission.",
    buttonText: "Verify Voters ID",
    provider: "youverify",
    requiresName: true
  },
  
  // === CRB SERVICES (keeping key ones) ===
  'crb': {
    title: "CRB <span>Status</span> Check",
    desc: "Check if you are listed or blacklisted with any Credit Reference Bureau in Kenya.",
    infoTitle: "What this CRB check includes",
    infoList: [
      "CRB listing or blacklist status",
      "Active and cleared defaults",
      "Delinquency history summary",
      "Safe enquiry - no score impact"
    ],
    inputLabel: "National ID Number",
    inputPlaceholder: "Enter National ID (e.g., 12345678)",
    inputHelp: "Format: 7-9 digits",
    inputIcon: "fa-shield-halved",
    formNote: "Check your CRB status. This check does NOT affect your credit score.",
    buttonText: "Check CRB Status",
    provider: "metropol"
  },
  
  'credit-score': {
    title: "Credit <span>Score</span> Lookup",
    desc: "Get your Metro credit score (0-900) for loans, BNPL, and hire purchase.",
    infoTitle: "What this Credit Score report includes",
    infoList: [
      "Current Metro credit score (0-900)",
      "Credit rating classification",
      "Payment performance index",
      "Score improvement insights"
    ],
    inputLabel: "National ID Number",
    inputPlaceholder: "Enter National ID (e.g., 12345678)",
    inputHelp: "Format: 7-9 digits",
    inputIcon: "fa-gauge-high",
    formNote: "View your current credit score and rating.",
    buttonText: "Get My Credit Score",
    provider: "metropol"
  },
  
  'tenant': {
    title: "Tenant <span>Verification</span>",
    desc: "AI-assisted tenant screening for landlords and property managers.",
    infoTitle: "What this Tenant screening includes",
    infoList: [
      "Previous rental payment history",
      "Eviction records and references",
      "Credit behaviour and delinquency",
      "Employment and income stability"
    ],
    inputLabel: "Tenant National ID Number",
    inputPlaceholder: "Enter Tenant ID (e.g., 12345678)",
    inputHelp: "Format: 7-9 digits",
    inputIcon: "fa-home",
    formNote: "Screen tenants for rental risk. Full privacy maintained.",
    buttonText: "Screen Tenant",
    provider: "metropol"
  },
  
  'job': {
    title: "Job <span>Verification</span>",
    desc: "Verify employment history, job title, and professional background.",
    infoTitle: "What this Employment verification includes",
    infoList: [
      "Current and previous employment",
      "Job title and duration verification",
      "Reference and performance signals",
      "Identity and qualification check"
    ],
    inputLabel: "Applicant National ID Number",
    inputPlaceholder: "Enter Applicant ID (e.g., 12345678)",
    inputHelp: "Format: 7-9 digits",
    inputIcon: "fa-briefcase",
    formNote: "Verify employment background for HR and recruitment.",
    buttonText: "Verify Applicant",
    provider: "metropol"
  }
};
