document.addEventListener("DOMContentLoaded", function () {
  // --- STATE VARIABLES ---
  let currentUserPreparedBy = "COMPUTER LAB CUSTODIAN"; // Default fallback

  // --- UI ELEMENTS ---
  const tabBtns = document.querySelectorAll(".tab-btn");
  const subTabBtns = document.querySelectorAll(".sub-tab");

  const snapshotDateInput = document.getElementById("snapshotDate");
  const fromDateInput = document.getElementById("fromDate");
  const toDateInput = document.getElementById("toDate");

  const snapshotDateGroup = document.getElementById("snapshotDateGroup");
  const dateRangeGroup = document.getElementById("dateRangeGroup");

  const labRoomSelect = document.getElementById("labRoomSelect");
  const generateBtn = document.getElementById("generateReportBtn");
  const reportPreviewArea = document.getElementById("reportPreviewArea");
  const exportBtn = document.getElementById("exportReportBtn");

  const labRoomGroup = document.getElementById("labRoomGroup");
  const subTabsWrapper = document.getElementById("subTabsWrapper");
  const statusChartContainer = document.getElementById("statusChartContainer");
  const mainDonutChart = document.getElementById("mainDonutChart");
  const condemnedCountPanel = document.getElementById("condemnedCountPanel");
  const statusLegend = document.getElementById("statusLegend");

  // --- NEW MOBILE UI ELEMENTS ---
  const reportLayout = document.getElementById("reportLayout");
  const backBtn = document.getElementById("backToGenerateBtn");

  /**
   * 1. DATA AGGREGATOR HELPER
   */
  function getPerRoomBreakdown(units, assets, isCondemned = false) {
    const rooms = {};
    const initRoom = (name) => {
      if (!rooms[name])
        rooms[name] = { name, uw: 0, ur: 0, aw: 0, ar: 0, uc: 0, ac: 0 };
    };

    units.forEach((u) => {
      initRoom(u.lab_room);
      if (isCondemned) rooms[u.lab_room].uc++;
      else {
        if (
          u.set_status.includes("Working") ||
          u.set_status.includes("In Stock")
        )
          rooms[u.lab_room].uw++;
        else rooms[u.lab_room].ur++;
      }
    });

    assets.forEach((a) => {
      initRoom(a.lab_room);
      if (isCondemned) rooms[a.lab_room].ac++;
      else {
        if (
          a.set_status.includes("Working") ||
          a.set_status.includes("In Stock")
        )
          rooms[a.lab_room].aw++;
        else rooms[a.lab_room].ar++;
      }
    });

    return Object.values(rooms).sort((a, b) => a.name.localeCompare(b.name));
  }

  /**
   * 2. CORE FETCH UTILITY
   */
  function fetchReportData(type, subTab) {
    const formData = new FormData();
    formData.append("action", "generate_snapshot_report");
    formData.append("type", type);
    formData.append("subTab", subTab);
    formData.append("labId", labRoomSelect.value);

    if (type === "condemned") {
      formData.append("fromDate", fromDateInput.value);
      formData.append("toDate", toDateInput.value);
    } else {
      formData.append("asOfDate", snapshotDateInput.value);
    }

    return fetch(window.location.href, {
      method: "POST",
      body: formData,
    }).then((response) => response.json());
  }

  /**
   * 3. REFRESH ROOM LIST
   */
  function refreshRoomList() {
    const activeTab = document
      .querySelector(".tab-btn.active")
      .getAttribute("data-tab");
    const savedRoomId = labRoomSelect.value;

    if (activeTab === "inventory") {
      updateVisualsOnly();
      return;
    }

    const formData = new FormData();
    formData.append("action", "fetch_snapshot_rooms");
    formData.append("type", activeTab);

    if (activeTab === "condemned") {
      formData.append("fromDate", fromDateInput.value);
      formData.append("toDate", toDateInput.value);
    } else {
      formData.append("asOfDate", snapshotDateInput.value);
    }

    fetch(window.location.href, { method: "POST", body: formData })
      .then((r) => r.json())
      .then((res) => {
        labRoomSelect.innerHTML =
          '<option value="all">All Laboratories</option>';
        if (res.success && res.data.length > 0) {
          res.data.forEach((room) => {
            const opt = document.createElement("option");
            opt.value = room.lab_id;
            opt.textContent = room.lab_room;
            labRoomSelect.appendChild(opt);
          });
          if (savedRoomId && savedRoomId !== "all") {
            const exists = res.data.some((r) => r.lab_id == savedRoomId);
            if (exists) labRoomSelect.value = savedRoomId;
          }
        }
        updateVisualsOnly();
      });
  }

  /**
   * 4. MAIN GENERATION DISPATCHER
   */
  function generateFormalReport() {
    const activeTab = document
      .querySelector(".tab-btn.active")
      .getAttribute("data-tab");
    const isAllLabs = labRoomSelect.value === "all";

    reportPreviewArea.innerHTML =
      '<div class="empty-state"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Generating Formal Report...</p></div>';

    if (activeTab === "status" || activeTab === "condemned") {
      Promise.all([
        fetchReportData(activeTab, "units"),
        fetchReportData(activeTab, "assets"),
      ]).then(([unitsRes, assetsRes]) => {
        if (unitsRes.prepared_by) currentUserPreparedBy = unitsRes.prepared_by;

        const data = {
          units: unitsRes.success ? unitsRes.data : [],
          assets: assetsRes.success ? assetsRes.data : [],
        };

        if (activeTab === "status") {
          if (isAllLabs) renderSummaryOnlyReport(data, currentUserPreparedBy);
          else renderDetailedRoomReport(data, currentUserPreparedBy);
        } else if (activeTab === "condemned") {
          if (isAllLabs)
            renderCondemnedSummaryOnly(data, currentUserPreparedBy);
          else renderCondemnedDetailed(data, currentUserPreparedBy);
        }
      });
    } else if (activeTab === "inventory") {
      fetchReportData("inventory", "units").then((res) => {
        if (res.prepared_by) currentUserPreparedBy = res.prepared_by;
        if (res.success) renderInventoryReport(res.data, currentUserPreparedBy);
      });
    }
  }

  // --- REUSABLE HELPERS ---

  function getReportHeaderHTML() {
    return `
            <div class="doc-header" style="position: relative; margin-bottom: 20px; font-family: 'Times New Roman', Times, serif; min-height: 80px; display: flex; align-items: center; justify-content: center;">
                
                <div style="position: absolute; left: 0; top: 0;">
                    <img src="../assets/cvsu_logo.png" style="width: 80px; height: 80px;">
                </div>
                
                <div style="text-align: center; width: 100%; padding: 0 90px; box-sizing: border-box;">
                    <p style="margin: 0; font-size: 14px;">Republic of the Philippines</p>
                    <h2 style="margin: 0; font-size: 20px; font-weight: 800; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                        CAVITE STATE UNIVERSITY
                    </h2>
                    <h3 style="margin: 0; font-size: 16px; font-weight: bold; font-family: 'Times New Roman', Times, serif;">
                        Cavite City Campus
                    </h3>
                    <p style="margin: 0; font-size: 13px;">Pulo II, Dalahican, Cavite City</p>
                </div>
                
            </div>
            <div class="doc-divider" style="border-bottom: 1.5px solid black; margin: 10px 0 20px 0;"></div>`;
  }

  function generateSignatureHTML(preparedBy) {
    return `
            <div class="signature-section" style="margin-top: auto; padding-top: 50px; display: flex; flex-direction: column; align-items: flex-start; gap: 35px; page-break-inside: avoid;">
                <div class="sig-box" style="width: 300px;">
                    <p style="margin-bottom: 5px; font-size: 14px; font-weight: bold; text-align: left;">Prepared By:</p>
                    <div style="text-align: center;">
                        <div style="height: 45px;"></div> 
                        <div style="border-top: 1.5px solid black; margin-bottom: 2px;"></div>
                        <div style="font-family: 'Times New Roman', serif; font-size: 14px; font-weight: bold; text-transform: uppercase;">
                            ${preparedBy}
                        </div>
                        <div style="font-size: 12px; color: #444;">Computer Lab Custodian</div>
                    </div>
                </div>
                <div class="sig-box" style="width: 300px;">
                    <p style="margin-bottom: 5px; font-size: 14px; font-weight: bold; text-align: left;">Approved By:</p>
                    <div style="text-align: center;">
                        <div style="height: 45px;"></div> 
                        <div style="border-top: 1.5px solid black; margin-bottom: 2px;"></div>
                    </div>
                </div>
            </div>`;
  }

  function calculateStats(items) {
    let working = 0,
      repair = 0;
    (items || []).forEach((i) => {
      if (i.set_status.includes("Working") || i.set_status.includes("In Stock"))
        working++;
      else repair++;
    });
    const total = (items || []).length;
    return {
      total,
      working,
      repair,
      wp: total > 0 ? Math.round((working / total) * 100) : 0,
      rp: total > 0 ? Math.round((repair / total) * 100) : 0,
    };
  }

  function renderListRows(items) {
    if (!items || items.length === 0)
      return '<tr><td colspan="2" style="text-align:center;">No records found.</td></tr>';
    return items
      .map(
        (item) =>
          `<tr><td>${item.set_tag}</td><td style="text-align:center;">${item.set_status}</td></tr>`,
      )
      .join("");
  }

  // --- RENDERERS ---

  /**
   * STATUS - SPECIFIC LABORATORY
   */
  function renderDetailedRoomReport(data, preparedBy) {
    const dateObj = new Date(snapshotDateInput.value);
    const formattedDate = dateObj.toLocaleDateString("en-US", {
      month: "long",
      day: "numeric",
      year: "numeric",
    });
    const labName = labRoomSelect.options[labRoomSelect.selectedIndex].text;
    const u = calculateStats(data.units);
    const a = calculateStats(data.assets);

    let html = `<div class="report-paper"><div class="report-body">`;
    html += getReportHeaderHTML();
    html += `
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 18px; margin: 0; font-weight: 800; font-family: 'Times New Roman', Times, serif;">COMPUTER LABORATORY REPORT</h2>
                <h3 style="font-size: 16px; margin: 0; font-weight: 800; font-family: 'Times New Roman', Times, serif;">UNITS AND ASSETS STATUS</h3>
                <p style="font-size: 16px; margin: 5px 0;">Room: ${labName}</p>
                <p style="font-size: 15px; margin: 10px 0;">As of ${formattedDate}</p>
            </div>
            
            <table class="doc-table" style="margin-bottom: 30px;">
                <thead>
                    <tr>
                        <th style="width: 40%; text-align: center;">Category</th>
                        <th style="text-align: center;">Working</th>
                        <th style="text-align: center;">For Repair</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Computer Units</td>
                        <td align="center">${u.working}</td>
                        <td align="center">${u.repair}</td>
                    </tr>
                    <tr>
                        <td>Facility Assets</td>
                        <td align="center">${a.working}</td>
                        <td align="center">${a.repair}</td>
                    </tr>
                </tbody>
            </table>
            
            <h4 class="table-label">Computer Units List</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead><tr><th style="width: 50%; text-align: center;">Tag</th><th style="text-align: center;">Status</th></tr></thead>
                <tbody>${renderListRows(data.units)}</tbody>
            </table>
            
            <h4 class="table-label">Facility Assets List</h4>
            <table class="doc-table">
                <thead><tr><th style="width: 50%; text-align: center;">Tag</th><th style="text-align: center;">Status</th></tr></thead>
                <tbody>${renderListRows(data.assets)}</tbody>
            </table>
        </div>${generateSignatureHTML(preparedBy)}</div>`;
    reportPreviewArea.innerHTML = html;
  }

  /**
   * CONDEMNED - SPECIFIC LABORATORY
   */
  function renderCondemnedDetailed(data, preparedBy) {
    const fDate = new Date(fromDateInput.value).toLocaleDateString("en-US", {
      month: "long",
      day: "numeric",
      year: "numeric",
    });
    const tDate = new Date(toDateInput.value).toLocaleDateString("en-US", {
      month: "long",
      day: "numeric",
      year: "numeric",
    });
    const labName = labRoomSelect.options[labRoomSelect.selectedIndex].text;

    let html = `<div class="report-paper"><div class="report-body">`;
    html += getReportHeaderHTML();
    html += `
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 18px; margin: 0; font-weight: 800; font-family: 'Times New Roman', Times, serif;">COMPUTER LABORATORY REPORT</h2>
                <h3 style="font-size: 16px; margin: 0; font-weight: 800; font-family: 'Times New Roman', Times, serif;">CONDEMNED STATUS (DETAILED)</h3>
                <p style="font-size: 16px; margin: 5px 0;">Room: ${labName}</p>
                <p style="font-size: 15px; margin: 10px 0;">Period: ${fDate} to ${tDate}</p>
            </div>
            
            <table class="doc-table" style="margin-bottom: 30px;">
                <thead>
                    <tr>
                        <th style="width: 40%; text-align: center;">Category</th>
                        <th style="text-align: center;">Total Condemned</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Computer Units</td>
                        <td align="center">${data.units.length}</td>
                    </tr>
                    <tr>
                        <td>Facility Assets</td>
                        <td align="center">${data.assets.length}</td>
                    </tr>
                </tbody>
            </table>

            <h4 class="table-label">Computer Units List</h4>
            <table class="doc-table" style="margin-bottom: 20px;">
                <thead><tr><th style="width: 50%; text-align: center;">Tag</th><th style="text-align: center;">Date Condemned</th></tr></thead>
                <tbody>${data.units.map((i) => `<tr><td>${i.set_tag}</td><td align="center">${i.log_date}</td></tr>`).join("") || '<tr><td colspan="2" align="center">No records</td></tr>'}</tbody>
            </table>
            
            <h4 class="table-label">Facility Assets List</h4>
            <table class="doc-table">
                <thead><tr><th style="width: 50%; text-align: center;">Tag</th><th style="text-align: center;">Date Condemned</th></tr></thead>
                <tbody>${data.assets.map((i) => `<tr><td>${i.set_tag}</td><td align="center">${i.log_date}</td></tr>`).join("") || '<tr><td colspan="2" align="center">No records</td></tr>'}</tbody>
            </table>
        </div>${generateSignatureHTML(preparedBy)}</div>`;
    reportPreviewArea.innerHTML = html;
  }

  /**
   * STATUS - ALL LABORATORIES
   */
  function renderSummaryOnlyReport(data, preparedBy) {
    const dateObj = new Date(snapshotDateInput.value);
    const formattedDate = dateObj.toLocaleDateString("en-US", {
      month: "long",
      day: "numeric",
      year: "numeric",
    });
    const u = calculateStats(data.units);
    const a = calculateStats(data.assets);
    const breakdown = getPerRoomBreakdown(data.units, data.assets);

    let html =
      `<div class="report-paper"><div class="report-body">` +
      getReportHeaderHTML() +
      `
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 18px; font-weight: 800; font-family: 'Times New Roman', serif;">COMPUTER LABORATORY REPORT</h2>
                <h3 style="font-size: 16px; font-weight: 800; font-family: 'Times New Roman', serif;">CONSOLIDATED SUMMARY</h3>
                <p>All Laboratories | As of ${formattedDate}</p>
            </div>
            
            <table class="doc-table" style="margin-bottom: 30px;">
                <thead>
                    <tr><th style="text-align: center;">Category</th><th align="center">Working</th><th align="center">For Repair</th></tr>
                </thead>
                <tbody>
                    <tr><td>Computer Units</td><td align="center">${u.working}</td><td align="center">${u.repair}</td></tr>
                    <tr><td>Facility Assets</td><td align="center">${a.working}</td><td align="center">${a.repair}</td></tr>
                </tbody>
            </table>

            <h4 class="table-label">Computer Labs Summary Breakdown</h4>
            <table class="doc-table">
                <thead>
                    <tr><th rowspan="2" style="text-align: center;">Lab Rooms</th><th colspan="2" align="center">Computer Units</th><th colspan="2" align="center">Facility Assets</th></tr>
                    <tr><th align="center">Working</th><th align="center">For Repair</th><th align="center">Working</th><th align="center">For Repair</th></tr>
                </thead>
                <tbody>
                    ${breakdown.map((r) => `<tr><td>${r.name}</td><td align="center">${r.uw}</td><td align="center">${r.ur}</td><td align="center">${r.aw}</td><td align="center">${r.ar}</td></tr>`).join("")}
                    <tr style="font-weight:bold; background:#f9f9f9;"><td>Total</td><td align="center">${u.working}</td><td align="center">${u.repair}</td><td align="center">${a.working}</td><td align="center">${a.repair}</td></tr>
                </tbody>
            </table>
        </div>${generateSignatureHTML(preparedBy)}</div>`;
    reportPreviewArea.innerHTML = html;
  }

  /**
   * CONDEMNED - ALL LABORATORIES
   */
  function renderCondemnedSummaryOnly(data, preparedBy) {
    const fDate = new Date(fromDateInput.value).toLocaleDateString("en-US", {
      month: "long",
      day: "numeric",
      year: "numeric",
    });
    const tDate = new Date(toDateInput.value).toLocaleDateString("en-US", {
      month: "long",
      day: "numeric",
      year: "numeric",
    });
    const breakdown = getPerRoomBreakdown(data.units, data.assets, true);

    let html =
      `<div class="report-paper"><div class="report-body">` +
      getReportHeaderHTML() +
      `
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 18px; font-weight: 800; font-family: 'Times New Roman', serif;">COMPUTER LABORATORY REPORT</h2>
                <h3 style="font-size: 16px; font-weight: 800; font-family: 'Times New Roman', serif;">CONDEMNED STATUS SUMMARY</h3>
                <p>All Laboratories | Period: ${fDate} to ${tDate}</p>
            </div>

            <table class="doc-table" style="margin-bottom: 30px;">
                <thead><tr><th style="text-align: center;">Category</th><th align="center">Total Condemned</th></tr></thead>
                <tbody>
                    <tr><td>Computer Units</td><td align="center">${data.units.length}</td></tr>
                    <tr><td>Facility Assets</td><td align="center">${data.assets.length}</td></tr>
                </tbody>
            </table>

            <h4 class="table-label">Computer Labs Summary</h4>
            <table class="doc-table">
                <thead>
                    <tr><th rowspan="2" style="text-align: center;">Lab Rooms</th><th colspan="2" align="center">Number of Condemned</th></tr>
                    <tr><th align="center">Computer Units</th><th align="center">Facility Assets</th></tr>
                </thead>
                <tbody>
                    ${breakdown.map((r) => `<tr><td>${r.name}</td><td align="center">${r.uc}</td><td align="center">${r.ac}</td></tr>`).join("")}
                    <tr style="font-weight:bold; background:#f9f9f9;"><td>Total</td><td align="center">${data.units.length}</td><td align="center">${data.assets.length}</td></tr>
                </tbody>
            </table>
        </div>${generateSignatureHTML(preparedBy)}</div>`;
    reportPreviewArea.innerHTML = html;
  }

  /**
   * INVENTORY REPORT
   */
  function renderInventoryReport(data, preparedBy) {
    const dateObj = new Date(snapshotDateInput.value);
    const formattedDate = dateObj.toLocaleDateString("en-US", {
      month: "long",
      day: "numeric",
      year: "numeric",
    });
    let inStock = 0,
      outStock = 0;
    data.forEach((item) => {
      if (item.set_status.includes("In Stock")) inStock++;
      else outStock++;
    });
    const total = data.length;
    const s = {
      ts: total,
      is: inStock,
      os: total - inStock,
      ip: total > 0 ? Math.round((inStock / total) * 100) : 0,
      op: total > 0 ? Math.round(((total - inStock) / total) * 100) : 0,
    };

    let html =
      `<div class="report-paper"><div class="report-body">` +
      getReportHeaderHTML() +
      `
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="font-size: 18px; font-weight: 800; font-family: 'Times New Roman', Times, serif;">COMPUTER LABORATORY REPORT</h2>
                <h3 style="font-size: 16px; font-weight: 800; font-family: 'Times New Roman', Times, serif;">SUPPLY INVENTORY SUMMARY</h3>
                <p style="font-size: 15px; margin: 10px 0;">As of ${formattedDate}</p>
            </div>
            <table class="doc-table">
                <thead><tr><th width="40%" style="text-align: center;">Status</th><th align="center">Count</th><th align="center">%</th></tr></thead>
                <tbody>
                    <tr><td>In Stock</td><td align="center">${s.is}</td><td align="center">${s.ip}%</td></tr>
                    <tr><td>Out of Stock</td><td align="center">${s.os}</td><td align="center">${s.op}%</td></tr>
                    <tr class="row-total"><td>Total</td><td align="center">${s.ts}</td><td align="center">100%</td></tr>
                </tbody>
            </table>
            <h4 class="table-label" style="margin-top:20px;">Inventory Supply List</h4>
            <table class="doc-table">
                <thead><tr><th width="60%" style="text-align: center;">Supply Name</th><th style="text-align: center;">Status</th></tr></thead>
                <tbody>${data.map((item) => `<tr><td>${item.set_tag}</td><td align="center">${item.set_status}</td></tr>`).join("")}</tbody>
            </table>
        </div>${generateSignatureHTML(preparedBy)}</div>`;
    reportPreviewArea.innerHTML = html;
  }

  // --- VISUAL UPDATER (DASHBOARD) ---
  function updateVisualsOnly() {
    const activeTab = document
      .querySelector(".tab-btn.active")
      .getAttribute("data-tab");
    const activeSub =
      activeTab === "inventory"
        ? "units"
        : document.querySelector(".sub-tab.active").getAttribute("data-sub");
    fetchReportData(activeTab, activeSub).then((res) => {
      if (res.success) {
        const data = res.data;
        if (activeTab === "condemned") {
          document.getElementById("totalCondemnedCount").textContent =
            data.length;
          return;
        }
        let posLabel = "Working",
          negLabel = "For Repair";
        let posColor = "#4CAF50",
          negColor = "#FFC107";
        if (activeTab === "inventory") {
          posLabel = "In Stock";
          negLabel = "Out of Stock";
          negColor = "#F44336";
        }
        const label1 = document.getElementById("legendText1");
        const label2 = document.getElementById("legendText2");
        const dot1 = document.getElementById("legendColor1");
        const dot2 = document.getElementById("legendColor2");
        if (label1) label1.textContent = posLabel;
        if (label2) label2.textContent = negLabel;
        if (dot1) dot1.style.backgroundColor = posColor;
        if (dot2) dot2.style.backgroundColor = negColor;
        let posCount = 0,
          negCount = 0;
        data.forEach((item) => {
          if (item.set_status.includes(posLabel)) posCount++;
          else negCount++;
        });
        const cW = document.getElementById("countWorking");
        const cR = document.getElementById("countRepair");
        if (cW) cW.textContent = posCount;
        if (cR) cR.textContent = negCount;
        const total = posCount + negCount;
        const negPercent = total > 0 ? (negCount / total) * 100 : 0;
        if (mainDonutChart)
          mainDonutChart.style.background =
            total > 0
              ? `conic-gradient(${negColor} 0% ${negPercent}%, ${posColor} ${negPercent}% 100%)`
              : "#eee";
      }
    });
  }

  // --- EVENT LISTENERS ---
  tabBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      tabBtns.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      const tab = this.getAttribute("data-tab");
      snapshotDateGroup.style.display = tab === "condemned" ? "none" : "block";
      dateRangeGroup.style.display = tab === "condemned" ? "flex" : "none";
      labRoomGroup.style.display = tab === "inventory" ? "none" : "block";
      subTabsWrapper.style.display = tab === "inventory" ? "none" : "flex";
      statusChartContainer.style.display =
        tab === "condemned" ? "none" : "flex";
      condemnedCountPanel.style.display = tab === "condemned" ? "flex" : "none";
      statusLegend.style.display = tab === "condemned" ? "none" : "block";
      refreshRoomList();
    });
  });

  subTabBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      subTabBtns.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      updateVisualsOnly();
    });
  });
  snapshotDateInput.addEventListener("change", refreshRoomList);
  fromDateInput.addEventListener("change", refreshRoomList);
  toDateInput.addEventListener("change", refreshRoomList);
  labRoomSelect.addEventListener("change", updateVisualsOnly);
  if (snapshotDateInput.value) refreshRoomList();
  if (exportBtn) exportBtn.addEventListener("click", () => window.print());

  // --- NEW: MOBILE VIEW TOGGLE EVENT LISTENERS ---

  // Updates the Generate Button Listener
  generateBtn.addEventListener("click", () => {
    generateFormalReport(); // Keep existing logic

    // Add mobile class toggle
    if (window.innerWidth <= 991 && reportLayout) {
      reportLayout.classList.add("mobile-preview-active");
      window.scrollTo({ top: 0, behavior: "smooth" }); // Scroll to top for better UX
    }
  });

  // Add back button listener
  if (backBtn && reportLayout) {
    backBtn.addEventListener("click", () => {
      reportLayout.classList.remove("mobile-preview-active");
    });
  }
});
