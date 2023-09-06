import React from 'react';

interface TodoDataItem {
  type: string;
  name: string;
  status: string;
}

const TodoItem: React.FC<TodoDataItem> = ({ type, name, status }) => {
  return (
    <div className="todo-item">
      <div className="todo-check">
        <input type="checkbox" name="" id="" className="todo-checkbox" />
      </div>
      <div className="todo-content">
        <div className="todo-details">
          <span className="todo-type">{type}</span>
          <span className="todo-name">{name}</span>
          <span className="todo-status">{status}</span>
        </div>
        <div>
          <button className="view-more-btn">View</button>
        </div>
      </div>
    </div>
  );
};

export default TodoItem;
