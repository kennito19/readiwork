// ============================================================================
// COMPREHENSIVE SERVICE PLACEHOLDERS AND HINTS - ALL 61 SERVICES
// Copy this to replace the services object in verify.php
// ============================================================================

const services = {
  // ============================================================================
  // METROPOL IDENTITY SERVICES (34 services)
  // ============================================================================
  
  'id-verification': {
    title: "National ID <span>Verification</span>",
    desc: "Instant verification of an identity number or document to confirm validity and authenticity.",
    infoTitle: "What this check includes",
    infoList: ["Identity number validity check","Document authenticity confirmation","Basic identity match status","Safe verification with no score impact"],
    inputLabel: "National ID Number",
    inputPlaceholder: "Enter 8 digits (e.g., 12345678 or 25219798)",
    inputIcon: "fa-id-card",
    formNote: "Enter your Kenyan National ID number. Only numbers, no spaces or dashes.",
    buttonText: "Verify ID",
    provider: "metropol",
    inputHint: "Format: 7-9 digits • Example: 12345678"
  },
  
  'soft-background': {
    title: "Soft <span>Background</span> Check",
    desc: "Non-invasive identity verification with no credit score impact.",
    infoTitle: "What this check includes",
    infoList: ["Identity verification","Basic background screening","No credit score impact","Privacy-focused check"],
    inputLabel: "National ID Number",
    inputPlaceholder: "Enter 8 digits (e.g., 12345678)",
    inputIcon: "fa-user-check",
    formNote: "Non-invasive verification. Full privacy guaranteed.",
    buttonText: "Run Background Check",
    provider: "metropol",
    inputHint: "Format: 7-9 digits • No letters or symbols"
  },
  
  'identity-check': {
    title: "Identity <span>Consistency</span> Check",
    desc: "Detect fake or altered IDs with name, phone, and address verification.",
    infoTitle: "What this check reveals",
    infoList: ["Name consistency verification","Phone number validation","Address verification","Fraud detection indicators"],
    inputLabel: "National ID Number",
    inputPlaceholder: "Enter 8 digits (e.g., 28078627)",
    inputIcon: "fa-fingerprint",
    formNote: "Advanced identity consistency verification.",
    buttonText: "Check Identity",
    provider: "metropol",
    inputHint: "Format: 7-9 digits only"
  },
  
  'crb': {
    title: "CRB <span>Status</span> Check",
    desc: "Instantly confirm whether you are listed or blacklisted with any Credit Reference Bureau in Kenya.",
    infoTitle: "What this check includes",
    infoList: ["CRB listing or blacklist status","Active and cleared defaults","Delinquency history summary","Safe enquiry (no score impact)"],
    inputLabel: "National ID Number",
    inputPlaceholder: "Enter 8 digits (e.g., 12345678)",
    inputIcon: "fa-shield-halved",
    formNote: "Your ID is used strictly for verification purposes.",
    buttonText: "Check CRB Status",
    provider: "metropol",
    inputHint: "Format: 7-9 digits"
  },
  
  'crb-clearance': {
    title: "CRB <span>Clearance</span> Check",
    desc: "Check if person is CRB listed - current or historical NPA.",
    infoTitle: "What this check includes",
    infoList: ["CRB listing status","Active defaults","Historical delinquency","Clearance status"],
    inputLabel: "National ID Number",
    inputPlaceholder: "Enter 8 digits (e.g., 12345678)",
    inputIcon: "fa-clipboard-check",
    formNote: "Safe enquiry with no credit score impact.",
    buttonText: "Check Clearance",
    provider: "metropol",
    inputHint: "Format: 7-9 numeric digits"
  },
  
  // Add inputHint to all other Metropol services...
  'crb-certificate': {
    inputHint: "Format: 7-9 digits • Example: 12345678"
  },
  'loan-defaulter': {
    inputHint: "Format: 7-9 digits only"
  },
  'financial-stress': {
    inputHint: "Format: 7-9 digits"
  },
  'high-risk-detection': {
    inputHint: "Format: 7-9 numeric digits"
  },
  'credit-score': {
    inputHint: "Format: 7-9 digits • Example: 25219798"
  },
  'loan-eligibility': {
    inputHint: "Format: 7-9 digits"
  },
  'creditworthiness': {
    inputHint: "Format: 7-9 digits only"
  },
  
  // ============================================================================
  // YOUVERIFY KENYA SERVICES
  // ============================================================================
  
  'yv-ke-id': {
    title: "Kenya ID <span>Verification</span>",
    desc: "Verify Kenyan National ID with photo match and biometric validation.",
    infoTitle: "What this verification includes",
    infoList: ["ID number validation with IPRS","Photo match verification","Biometric data validation","Real-time verification status"],
    inputLabel: "National ID Number",
    inputPlaceholder: "8 digits (e.g., 25219798 or 28078627)",
    inputIcon: "fa-id-card",
    formNote: "Enter ID, first name, and last name as they appear on your ID card.",
    buttonText: "Verify ID",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 8 digits • Test IDs: 25219798, 28078627"
  },
  
  'yv-ke-passport': {
    title: "Kenya Passport <span>Verification</span>",
    desc: "Verify Kenyan passport authenticity and validity.",
    infoTitle: "What this verification includes",
    infoList: ["Passport number validation","Document authenticity check","Photo and biometric validation","Travel document status"],
    inputLabel: "Passport Number",
    inputPlaceholder: "Letter + digits (e.g., A2081731 or A1998653)",
    inputIcon: "fa-passport",
    formNote: "Enter passport number exactly as shown on your passport document.",
    buttonText: "Verify Passport",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 1 letter + 7 digits • Example: A2081731"
  },
  
  'yv-ke-drivers-license': {
    title: "Kenya Drivers License <span>Verification</span>",
    desc: "Verify Kenyan driving license validity and authenticity.",
    infoTitle: "What this verification includes",
    infoList: ["License number validation","NTSA database check","Driver information verification","License status and validity"],
    inputLabel: "Drivers License Number",
    inputPlaceholder: "10-15 characters (e.g., DL12345678)",
    inputIcon: "fa-id-card-clip",
    formNote: "Enter license number, names, and date of birth as on your license.",
    buttonText: "Verify License",
    provider: "youverify",
    requiresName: true,
    requiresDOB: true,
    inputHint: "Format: Letters + numbers • Example: DL12345678"
  },
  
  'yv-ke-alien-id': {
    title: "Kenya Alien ID <span>Verification</span>",
    desc: "Verify Alien/Foreigner ID in Kenya.",
    infoTitle: "What this verification includes",
    infoList: ["Alien ID validation","Immigration records check","Personal details verification","Residence status confirmation"],
    inputLabel: "Alien ID Number",
    inputPlaceholder: "9-12 digits (e.g., 123456789)",
    inputIcon: "fa-id-card",
    formNote: "Enter Alien ID number as shown on your Alien Card.",
    buttonText: "Verify Alien ID",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 9-12 numeric digits"
  },
  
  'yv-ke-bank-account': {
    title: "Kenya Bank Account <span>Verification</span>",
    desc: "Verify Kenyan bank account details and ownership.",
    infoTitle: "What this verification includes",
    infoList: ["Account number validation","Bank verification","Account holder confirmation","Account status check"],
    inputLabel: "Bank Account Number",
    inputPlaceholder: "10-16 digits (e.g., 1234567890)",
    inputIcon: "fa-building-columns",
    formNote: "Enter your bank account number and select your bank from the dropdown.",
    buttonText: "Verify Account",
    provider: "youverify",
    requiresBankCode: true,
    inputHint: "Format: 10-16 digits • No spaces or dashes"
  },
  
  'yv-ke-tax': {
    title: "KRA PIN <span>Verification</span>",
    desc: "Verify Kenya Revenue Authority PIN number.",
    infoTitle: "What this verification includes",
    infoList: ["KRA PIN validation","Tax compliance status","Taxpayer details verification","Registration status"],
    inputLabel: "KRA PIN Number",
    inputPlaceholder: "Letter + digits + letter (e.g., A012345678X)",
    inputIcon: "fa-receipt",
    formNote: "Enter KRA PIN exactly as shown on your KRA certificate.",
    buttonText: "Verify KRA PIN",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: A + 9 digits + letter • Example: A012345678X"
  },
  
  'yv-ke-address': {
    title: "Kenya Address <span>Verification</span>",
    desc: "Verify physical address in Kenya with supporting documents.",
    infoTitle: "What this verification includes",
    infoList: ["Address validation","Utility bill verification","Resident confirmation","Geographic location check"],
    inputLabel: "National ID Number",
    inputPlaceholder: "8 digits (e.g., 12345678)",
    inputIcon: "fa-location-dot",
    formNote: "Enter your ID and complete physical address with street name and building details.",
    buttonText: "Verify Address",
    provider: "youverify",
    requiresName: true,
    requiresAddress: true,
    inputHint: "Format: 8 digits • Address field required below"
  },
  
  'yv-ke-phone': {
    title: "Kenya Phone <span>Verification</span>",
    desc: "Verify Kenyan phone number ownership and validity.",
    infoTitle: "What this verification includes",
    infoList: ["Phone number validation","Ownership verification","Carrier information","Registration status"],
    inputLabel: "National ID Number",
    inputPlaceholder: "8 digits (e.g., 12345678)",
    inputIcon: "fa-mobile-screen",
    formNote: "Enter the ID used to register this phone number.",
    buttonText: "Verify Phone",
    provider: "youverify",
    requiresPhone: true,
    inputHint: "Format: 8 digits • Phone number field required below"
  },
  
  'yv-ke-employment': {
    title: "Kenya Employment <span>Verification</span>",
    desc: "Verify employment status and history in Kenya.",
    infoTitle: "What this verification includes",
    infoList: ["Employment status check","Employer verification","Job title confirmation","Employment duration"],
    inputLabel: "National ID Number",
    inputPlaceholder: "8 digits (e.g., 12345678)",
    inputIcon: "fa-briefcase",
    formNote: "Enter your ID, current employer name, and job title.",
    buttonText: "Verify Employment",
    provider: "youverify",
    requiresName: true,
    requiresEmployment: true,
    inputHint: "Format: 8 digits • Employment details required below"
  },
  
  'yv-ke-plate-number': {
    title: "Kenya Vehicle Plate <span>Verification</span>",
    desc: "Verify vehicle registration with NTSA.",
    infoTitle: "What this verification includes",
    infoList: ["Plate number validation","NTSA registration check","Vehicle ownership details","Registration status"],
    inputLabel: "Vehicle Plate Number",
    inputPlaceholder: "Plate format (e.g., KAA 123X or KBZ 456Y)",
    inputIcon: "fa-car",
    formNote: "Enter vehicle plate number as shown on your number plate.",
    buttonText: "Verify Vehicle",
    provider: "youverify",
    inputHint: "Format: 3 letters + space + 3 digits + letter • Example: KAA 123X"
  },
  
  'yv-ke-vehicle-collateral': {
    title: "Kenya Vehicle Collateral <span>Details</span>",
    desc: "Comprehensive vehicle details for collateral verification.",
    infoTitle: "What this check includes",
    infoList: ["Complete vehicle details","NTSA registration info","Ownership history","Encumbrance status"],
    inputLabel: "Vehicle Plate Number",
    inputPlaceholder: "Plate format (e.g., KAA 123X)",
    inputIcon: "fa-car",
    formNote: "Enter plate number and chassis number for full vehicle details.",
    buttonText: "Get Vehicle Details",
    provider: "youverify",
    requiresChassis: true,
    inputHint: "Format: KAA 123X • Chassis number field required below"
  },
  
  'yv-ke-business': {
    title: "Kenya Business <span>Verification</span>",
    desc: "Verify business registration with BRS Kenya.",
    infoTitle: "What this verification includes",
    infoList: ["Business registration validation","Company directors verification","Registration status check","Business details confirmation"],
    inputLabel: "Business Registration Number",
    inputPlaceholder: "Registration number (e.g., PVT-1234567890)",
    inputIcon: "fa-building",
    formNote: "Enter business registration number and registered business name.",
    buttonText: "Verify Business",
    provider: "youverify",
    requiresBusinessName: true,
    inputHint: "Format: PVT-XXXXXXXXXX or CPR/XXXX/XXXX"
  },
  
  // ============================================================================
  // YOUVERIFY NIGERIA SERVICES
  // ============================================================================
  
  'yv-ng-bvn': {
    title: "Nigeria BVN <span>Verification</span>",
    desc: "Verify Nigerian Bank Verification Number.",
    infoTitle: "What this verification includes",
    infoList: ["BVN validation with NIBSS","Personal details confirmation","Photo match verification","Bank account linkage"],
    inputLabel: "BVN Number",
    inputPlaceholder: "11 digits (e.g., 12345678901)",
    inputIcon: "fa-building-columns",
    formNote: "Enter BVN number exactly as shown on your banking records.",
    buttonText: "Verify BVN",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 11 numeric digits • Example: 12345678901"
  },
  
  'yv-ng-nin': {
    title: "Nigeria NIN <span>Verification</span>",
    desc: "Verify Nigerian National Identification Number.",
    infoTitle: "What this verification includes",
    infoList: ["NIN validation with NIMC","Personal details verification","Biometric data check","Identity document status"],
    inputLabel: "NIN Number",
    inputPlaceholder: "11 digits (e.g., 12345678901)",
    inputIcon: "fa-id-card",
    formNote: "Enter NIN as shown on your NIMC slip or ID card.",
    buttonText: "Verify NIN",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 11 numeric digits"
  },
  
  // ============================================================================
  // YOUVERIFY GHANA SERVICES
  // ============================================================================
  
  'yv-gh-drivers-license': {
    title: "Ghana Drivers License <span>Verification</span>",
    desc: "Verify Ghana driving license.",
    infoTitle: "What this verification includes",
    infoList: ["License validation","DVLA database check","Driver information","License status"],
    inputLabel: "Ghana License Number",
    inputPlaceholder: "Letter + digits (e.g., G1234567)",
    inputIcon: "fa-id-card-clip",
    formNote: "Enter Ghana DVLA license number.",
    buttonText: "Verify License",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: Letter + 7 digits • Example: G1234567"
  },
  
  'yv-gh-voters-id': {
    title: "Ghana Voters ID <span>Verification</span>",
    desc: "Verify Ghana Electoral Commission voters ID.",
    infoTitle: "What this verification includes",
    infoList: ["Voters ID validation","Electoral register check","Voter details verification","Registration status"],
    inputLabel: "Voters ID Number",
    inputPlaceholder: "10-12 digits (e.g., 1234567890)",
    inputIcon: "fa-id-card",
    formNote: "Enter voters ID as shown on your EC voter card.",
    buttonText: "Verify Voters ID",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 10-12 numeric digits"
  },
  
  // ============================================================================
  // YOUVERIFY OTHER AFRICAN COUNTRIES
  // ============================================================================
  
  'yv-za-id': {
    title: "South Africa ID <span>Verification</span>",
    desc: "Verify South African national ID.",
    infoTitle: "What this verification includes",
    infoList: ["ID number validation","Home Affairs database check","Personal details verification","ID document status"],
    inputLabel: "South African ID Number",
    inputPlaceholder: "13 digits (e.g., 9001010001088)",
    inputIcon: "fa-id-card",
    formNote: "Enter 13-digit SA ID number.",
    buttonText: "Verify ID",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 13 digits (YYMMDD + 4 digits + 1 + 8 + 1)"
  },
  
  'yv-ug-nin': {
    title: "Uganda NIN <span>Verification</span>",
    desc: "Verify Uganda National Identification Number.",
    infoTitle: "What this verification includes",
    infoList: ["NIN validation","NIRA database check","Personal details verification","ID status"],
    inputLabel: "Uganda NIN",
    inputPlaceholder: "14 characters (e.g., CM12345678ABC)",
    inputIcon: "fa-id-card",
    formNote: "Enter Uganda NIRA NIN number.",
    buttonText: "Verify NIN",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 2 letters + 8 digits + 3 letters • Example: CM12345678ABC"
  },
  
  'yv-tz-nin': {
    title: "Tanzania NIN <span>Verification</span>",
    desc: "Verify Tanzania National Identification Number.",
    infoTitle: "What this verification includes",
    infoList: ["NIN validation","NIDA database check","Personal details verification","ID status"],
    inputLabel: "Tanzania NIN",
    inputPlaceholder: "20 digits (e.g., 12345678901234567890)",
    inputIcon: "fa-id-card",
    formNote: "Enter Tanzania NIDA NIN number.",
    buttonText: "Verify NIN",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 20 numeric digits"
  },
  
  'yv-rw-nin': {
    title: "Rwanda NIN <span>Verification</span>",
    desc: "Verify Rwanda National Identification Number.",
    infoTitle: "What this verification includes",
    infoList: ["NIN validation","NIDA database check","Personal details verification","ID status"],
    inputLabel: "Rwanda NIN",
    inputPlaceholder: "16 digits (e.g., 1234567890123456)",
    inputIcon: "fa-id-card",
    formNote: "Enter Rwanda NIDA NIN number.",
    buttonText: "Verify NIN",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 16 numeric digits"
  },
  
  // ============================================================================
  // YOUVERIFY ADVANCED SERVICES
  // ============================================================================
  
  'yv-identity': {
    title: "Identity <span>Verification</span>",
    desc: "General identity verification service.",
    infoTitle: "What this verification includes",
    infoList: ["Identity validation","Personal details confirmation","Document verification","Identity status check"],
    inputLabel: "Identity Number",
    inputPlaceholder: "Enter ID number (e.g., 12345678)",
    inputIcon: "fa-fingerprint",
    formNote: "General identity verification.",
    buttonText: "Verify Identity",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 7-20 alphanumeric characters"
  },
  
  'yv-liveness': {
    title: "Biometric <span>Liveness</span> Check",
    desc: "Anti-spoofing face liveness detection for enhanced security.",
    infoTitle: "What this check includes",
    infoList: ["Live face detection","Anti-spoofing verification","Biometric matching","Real-time liveness assessment"],
    inputLabel: "National ID Number",
    inputPlaceholder: "8 digits (e.g., 12345678)",
    inputIcon: "fa-face-smile",
    formNote: "Biometric liveness requires ID and names for verification.",
    buttonText: "Check Liveness",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: 8 digits • Biometric scan required"
  },
  
  'yv-document': {
    title: "Document <span>Verification</span>",
    desc: "AI-powered document authenticity check for any document type.",
    infoTitle: "What this check includes",
    infoList: ["Document authenticity analysis","AI-powered forgery detection","Data extraction and validation","Tamper detection"],
    inputLabel: "Document Reference Number",
    inputPlaceholder: "Document ID (e.g., DOC12345678)",
    inputIcon: "fa-file-invoice",
    formNote: "Enter document reference number for verification.",
    buttonText: "Verify Document",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: Alphanumeric reference number"
  },
  
  'yv-sanctions-pep': {
    title: "Sanctions & PEP <span>Screening</span>",
    desc: "Screen against global sanctions and PEP lists.",
    infoTitle: "What this screening includes",
    infoList: ["Global sanctions database check","PEP (Politically Exposed Person) screening","Watchlist verification","Compliance reporting"],
    inputLabel: "Full Name",
    inputPlaceholder: "Full legal name (e.g., John Doe)",
    inputIcon: "fa-user-shield",
    formNote: "Enter full name and date of birth for comprehensive screening.",
    buttonText: "Screen Person",
    provider: "youverify",
    requiresName: true,
    requiresDOB: true,
    inputHint: "Enter: Full name • DOB required for accurate screening"
  },
  
  'yv-adverse-media': {
    title: "Adverse Media <span>Screening</span>",
    desc: "Screen for negative news and media mentions.",
    infoTitle: "What this screening includes",
    infoList: ["Global news database scan","Negative media mentions","Risk indicators","Reputation assessment"],
    inputLabel: "Full Name",
    inputPlaceholder: "Full legal name (e.g., John Doe)",
    inputIcon: "fa-newspaper",
    formNote: "Enter full name for media screening.",
    buttonText: "Screen Media",
    provider: "youverify",
    requiresName: true,
    inputHint: "Format: First and last name required"
  }
};

// NOTE: For remaining Metropol services not shown above, add similar inputHint fields:
// Example template:
// 'service-key': {
//   inputHint: "Format: 7-9 digits • Example: 12345678"
// }
