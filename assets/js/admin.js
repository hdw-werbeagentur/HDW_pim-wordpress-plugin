jQuery(document).ready(function () {
  const Import = {
    boot: function () {
      Import.timer = jQuery("#import-timer");
      Import.timerCounter = 0;
      Import.timerInterval = null;
      Import.draftProducts = jQuery("#skip-draft-shop-products");
      Import.importButton = jQuery("#product-import");
      Import.importProgress = jQuery("#product-progress");
      Import.allProducts = jQuery(".product-input:checked");
      Import.products = Import.allProducts.filter(":checked");
      Import.currentImport = 0;
      Import.currentProduct = jQuery(Import.products[Import.currentImport]);
      Import.itemsCount = Import.products.length;
      Import.filters = jQuery(".button-filter");

      Import.bindings();
    },

    bindings: function () {
      Import.importButton.click(function (e) {
        e.preventDefault();
        Import.startImport();
      });

      Import.filters.click(function (e) {
        e.preventDefault();
        Import.filter(jQuery(this));
      });
    },

    filter: function (filter) {
      if (filter.prop("id") == "reset-selection") {
        Import.allProducts.prop("checked", false);
      } else if (filter.prop("id") == "select-all") {
        Import.allProducts.prop("checked", true);
      } else if (filter.prop("id") == "out-of-sync-selection") {
        Import.allProducts.prop("checked", false);
        jQuery('.product--is-out-of-sync input').prop('checked', true);
      } else if (filter.prop("id") == "invert-selection") {
        Import.allProducts.each(function (i, e) {
          var element = jQuery(e);
          if (element.prop("checked")) {
            element.prop("checked", false);
          } else {
            element.prop("checked", true);
          }
        });
      }
    },

    startImport: function () {
      Import.resetImport();
      Import.startTimer();
      Import.products = jQuery(".product-input:checked");
      jQuery(".product--is-imported").removeClass("product--is-imported");
      Import.importProgress
        .val(1)
        .text(1)
        .attr("max", Import.products.length)
        .css("opacity", 1);

      Import.products = jQuery(".product-input").filter(":checked");
      Import.currentImport = 0;
      Import.currentProduct = jQuery(Import.products[Import.currentImport]);
      Import.importProduct(Import.currentProduct);
    },

    importProduct: function (product) {
      var container = Import.currentProduct.closest(".product"),
        data = {
          id: product.val(),
          action: "importProduct",
          progressIteration: Import.currentImport + 1,
          progressLimit: Import.products.length,
        };
      container.addClass("product--is-importing");
      jQuery
        .post(ajaxurl, data)
        .done(function (response) {
          container.removeClass("product--is-importing");

          if (response.success) {
            container.addClass("product--is-imported");
          } else {
            container.addClass("product--has-error");
          }

          Import.currentImport++;
          Import.importProgress
            .val(Import.currentImport)
            .text(Import.currentImport);
          Import.currentProduct = jQuery(Import.products[Import.currentImport]);

          if (Import.currentProduct.val()) {
            Import.importProduct(Import.currentProduct);
          } else {
            Import.finishImport();
          }
        })
        .fail(function (xhr, status, error) {
          container
            .removeClass("product--is-importing")
            .addClass("product--has-error");

          Import.currentImport++;
          Import.importProgress
            .val(Import.currentImport)
            .text(Import.currentImport);
          Import.currentProduct = jQuery(Import.products[Import.currentImport]);

          if (Import.currentProduct.val()) {
            Import.importProduct(Import.currentProduct);
          } else {
            Import.finishImport();
          }
        });
    },

    finishImport: function () {
      Import.importProgress.css("opacity", 0);
      Import.stopTimer();
    },

    resetImport: function () {
      Import.importProgress.css("opacity", 0);
      Import.stopTimer();
      jQuery(".product")
        .removeClass("product--is-importing")
        .removeClass("product--is-imported")
        .removeClass("product--has-error");
    },

    startTimer: function () {
      Import.timerCounter = 0;
      Import.timer.css("opacity", 1).text("00:00");
      Import.timerInterval = setInterval(function () {
        Import.timerCounter++;
        var seconds = Import.timerCounter % 60;
        var minutes = Math.floor(Import.timerCounter / 60);
        var counter =
          minutes.toString().padStart(2, 0) +
          ":" +
          seconds.toString().padStart(2, 0);
        Import.timer.text(counter);
      }, 1000);
    },

    stopTimer: function () {
      clearInterval(Import.timerInterval);
      // Import.timer.css("opacity", 0);
    }
  };

  Import.boot();
});
