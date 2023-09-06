import React from 'react';

interface UserProfileProps {
  userName: string;
  date: string;
  userDescription: string;
}

const UserProfile: React.FC<UserProfileProps> = ({ userName, date, userDescription }) => {
  return (
    <div className="user-profile">
      <div className="user-img-wrap">
        <img src="/users/user-1.png" alt="" className="user-img" />
      </div>
      <div className="user-info">
        <span className="user-name">{userName}</span>
        <span className="date">{date}</span>
        <span className="user-description">{userDescription}</span>
        <div className="user-actions">
          <a href="" className="card-link">
            View
          </a>
          <button className="msg-btn">Message</button>
        </div>
      </div>
    </div>
  );
};

export default UserProfile;
