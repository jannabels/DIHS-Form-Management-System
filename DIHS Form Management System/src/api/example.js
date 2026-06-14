function exampleHandler(req, res) {
  try {
    const { name } = req.query;
    
    if (!name) {
      return res.status(400).json({ 
        success: false, 
        error: 'Name is required' 
      });
    }
    
    return res.status(200).json({ 
      success: true, 
      message: `Hello, ${name}!` 
    });
  } catch (error) {
    console.error('Error in exampleHandler:', error);
    return res.status(500).json({ 
      success: false, 
      error: 'Internal server error' 
    });
  }
}

module.exports = { exampleHandler };
