class Utility {
  /**
   *
   * @param element
   * @param display
   */
  toggleElement(element: Element | null, display: string | null = null) {
    if (!element) return;
    if (display !== null) {
      element.style.display = display;
      return;
    }
    if (element.style.display === "none") {
      element.style.display = "";
    } else {
      element.style.display = "none";
    }
  }
}

export default new Utility();
